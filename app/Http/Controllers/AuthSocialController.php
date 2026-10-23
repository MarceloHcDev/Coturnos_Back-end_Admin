<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthSocialController extends Controller
{
    /**
     * Lista fechada de provedores sociais suportados.
     * [LIÇÃO DE SEGURANÇA]: Whitelist estrita para impedir que valores arbitrários
     * da URL sejam passados para Socialite::driver().
     *
     * @var array<int, string>
     */
    protected array $allowedProviders = [
        'google',
        'facebook',
        'apple',
    ];

    /**
     * Valida se o provedor solicitado está na whitelist oficial.
     */
    protected function validateProvider(string $provider): void
    {
        if (! in_array($provider, $this->allowedProviders, true)) {
            abort(400, "Provedor social '{$provider}' não suportado.");
        }
    }

    /**
     * Redireciona o usuário para o fluxo de OAuth do provedor selecionado.
     * Suporta requisição direta de browser (redirecionamento 302) e SPA (retorna JSON com target URL).
     *
     * GET /api/v1/auth/{provider}/redirect
     */
    public function redirect(Request $request, string $provider): RedirectResponse|JsonResponse
    {
        $this->validateProvider($provider);

        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider)->stateless();

        if ($request->wantsJson()) {
            return response()->json([
                'url' => $driver->redirect()->getTargetUrl(),
            ]);
        }

        return $driver->redirect();
    }

    /**
     * Processa o retorno OAuth do provedor com regra anti-sequestro de conta (anti account-takeover).
     *
     * FLUXO DE SEGURANÇA:
     * 1. Busca vínculo existente por provider_name + provider_id. Se existir, autentica imediatamente.
     * 2. Se não existir, verifica se o e-mail foi formalmente validado pelo provedor.
     * 3. Busca usuário existente pelo e-mail:
     *    - Não existe: cria novo User com senha nula e vincula SocialAccount.
     *    - Existe E e-mail verificado: vincula SocialAccount à conta existente com segurança.
     *    - Existe E e-mail NÃO verificado: bloqueia vínculo automático e solicita confirmação de senha.
     *
     * GET /api/v1/auth/{provider}/callback
     */
    public function callback(Request $request, string $provider): JsonResponse|RedirectResponse
    {
        $this->validateProvider($provider);

        $frontUrl = config('app.front_url', env('FRONT_URL', 'http://localhost:5173'));

        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($provider)->stateless();

            if (! app()->runningUnitTests() && config('app.env') !== 'production' && method_exists($driver, 'setHttpClient')) {
                $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            }

            /** @var \Laravel\Socialite\Contracts\User $socialUser */
            $socialUser = $driver->user();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Erro no callback social ' . $provider, [
                'error' => $e->getMessage(),
            ]);

            $msg = 'Falha ao autenticar com ' . ucfirst($provider) . (config('app.debug') ? ': ' . $e->getMessage() : '.');
            if (! $request->wantsJson()) {
                return redirect("{$frontUrl}/auth/callback?error=" . urlencode($msg));
            }
            return response()->json([
                'message' => $msg,
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 400);
        }

        $providerId = (string) $socialUser->getId();
        $email = strtolower(trim((string) $socialUser->getEmail()));

        if (empty($email)) {
            $msg = 'Não foi possível obter um e-mail válido da conta social.';
            if (! $request->wantsJson()) {
                return redirect("{$frontUrl}/auth/callback?error=" . urlencode($msg));
            }
            return response()->json([
                'message' => $msg,
            ], 422);
        }

        // [WHITELIST]: Verifica se o e-mail está autorizado (proteção de homologação)
        if (! $this->isEmailAllowed($email)) {
            $msg = 'Acesso social restrito em ambiente de homologação. E-mail não autorizado para testes.';
            if (! $request->wantsJson()) {
                return redirect("{$frontUrl}/auth/callback?error=" . urlencode($msg));
            }
            return response()->json([
                'message' => $msg,
            ], 403);
        }

        // a) Buscar vínculo existente por provider_name + provider_id
        $socialAccount = SocialAccount::findByProvider($provider, $providerId);

        if ($socialAccount) {
            $user = $socialAccount->user;

            if (! $user || ! $user->active) {
                $msg = 'Esta conta está inativa. Entre em contato com o suporte.';
                if (! $request->wantsJson()) {
                    return redirect("{$frontUrl}/auth/callback?error=" . urlencode($msg));
                }
                return response()->json([
                    'message' => $msg,
                ], 403);
            }

            return $this->respondWithAuth($request, $user, 'Login social realizado com sucesso.', 200);
        }

        // b) Se não houver vínculo prévio, checa se o e-mail é verificado
        $isVerified = $this->isEmailVerified($provider, $socialUser);

        // c) Busca usuário existente pelo e-mail
        $existingUser = User::where('email', $email)->first();

        // Cenário 1: Novo usuário -> Cadastro limpo com senha nula e vínculo seguro
        if (! $existingUser) {
            $fullName = trim((string) ($socialUser->getName() ?? ''));
            $parts = explode(' ', $fullName, 2);
            $firstName = $parts[0] !== '' ? $parts[0] : 'Usuário';
            $lastName = $parts[1] ?? '';

            // [LIÇÃO]: Apenas atributos estritamente necessários são passados para create()
            $newUser = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => null,
            ]);

            // [LIÇÃO ANTI-IDOR]: Vínculo sempre via relacionamento, nunca passando user_id solto
            $newUser->socialAccounts()->create([
                'provider_name' => $provider,
                'provider_id' => $providerId,
            ]);

            return $this->respondWithAuth($request, $newUser, 'Cadastro social realizado com sucesso.', 201);
        }

        // Cenário 2: Usuário já existe E e-mail foi verificado pelo provedor -> Vincula e autentica
        if ($isVerified) {
            if (! $existingUser->active) {
                $msg = 'Esta conta está inativa. Entre em contato com o suporte.';
                if (! $request->wantsJson()) {
                    return redirect("{$frontUrl}/auth/callback?error=" . urlencode($msg));
                }
                return response()->json([
                    'message' => $msg,
                ], 403);
            }

            // Vincula novo provedor à conta existente
            $existingUser->socialAccounts()->create([
                'provider_name' => $provider,
                'provider_id' => $providerId,
            ]);

            return $this->respondWithAuth($request, $existingUser, 'Conta vinculada e login realizado com sucesso.', 200);
        }

        // Cenário 3: Usuário já existe, mas e-mail NÃO foi confirmado pelo provedor
        // [LIÇÃO ANTI ACCOUNT-TAKEOVER]: Bloqueia auto-vínculo para impedir sequestro de conta
        if (! $request->wantsJson()) {
            return redirect("{$frontUrl}/login?error=" . urlencode('Este e-mail já possui cadastro. Confirme sua senha para vincular a conta social.'));
        }

        return response()->json([
            'message' => 'Este e-mail já possui cadastro. Como o e-mail não foi verificado pelo provedor, confirme sua senha para vincular a conta com segurança.',
            'requires_password_confirmation' => true,
            'email' => $email,
            'provider' => $provider,
            'provider_id' => $providerId,
        ], 409);
    }

    /**
     * Retorna a resposta de autenticação adequada para API ou SPA browser.
     */
    protected function respondWithAuth(Request $request, User $user, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        $token = $user->createToken('social-token', $user->tokenAbilities(), now()->addDays(7))->plainTextToken;

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'user' => new UserResource($user),
                'token' => $token,
            ], $status);
        }

        $frontUrl = config('app.front_url', env('FRONT_URL', 'http://localhost:5173'));
        $userJson = urlencode(json_encode((new UserResource($user))->resolve()));
        return redirect("{$frontUrl}/auth/callback?token={$token}&user={$userJson}");
    }

    /**
     * Confirma a senha atual da conta para completar o vínculo manual de um provedor cujo e-mail não foi verificado.
     *
     * POST /api/v1/auth/{provider}/confirm-link
     */
    public function confirmLink(Request $request, string $provider): JsonResponse
    {
        $this->validateProvider($provider);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'provider_id' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower(trim($validated['email'])))->first();

        if (! $user || empty($user->password) || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas para confirmação de vínculo.',
            ], 401);
        }

        if (! $user->active) {
            return response()->json([
                'message' => 'Esta conta está inativa. Entre em contato com o suporte.',
            ], 403);
        }

        // Cria o vínculo após autenticação segura de titularidade da senha
        $user->socialAccounts()->create([
            'provider_name' => $provider,
            'provider_id' => $validated['provider_id'],
        ]);

        $token = $user->createToken('social-token', $user->tokenAbilities(), now()->addDays(7))->plainTextToken;

        return response()->json([
            'message' => 'Conta vinculada com sucesso após confirmação.',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Checa se o e-mail retornado pelo provedor foi autenticado/verificado por ele.
     * Regra estrita: Se o provedor não informar essa flag ou vier false, trata como NÃO verificado.
     */
    protected function isEmailVerified(string $provider, \Laravel\Socialite\Contracts\User $socialUser): bool
    {
        $raw = $socialUser->getRaw();

        $verified = match ($provider) {
            'google' => $raw['email_verified'] ?? $raw['verified_email'] ?? false,
            'facebook' => $raw['email_verified'] ?? false,
            'apple' => $raw['email_verified'] ?? false,
            default => false,
        };

        return filter_var($verified, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Verifica se o e-mail retornado pelo provedor está na whitelist de homologação.
     *
     * Se SOCIAL_AUTH_ALLOWED_EMAILS estiver vazio (produção), libera todos.
     * Se preenchido (homologação/testes), só permite os e-mails da lista.
     */
    protected function isEmailAllowed(string $email): bool
    {
        $allowed = config('services.social_allowed_emails', '');

        if (empty(trim((string) $allowed))) {
            return true; // Produção: sem restrição
        }

        $list = array_map('trim', explode(',', strtolower((string) $allowed)));

        return in_array(strtolower($email), $list, true);
    }
}
