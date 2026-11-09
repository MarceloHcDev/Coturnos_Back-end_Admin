<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ImageController extends Controller
{
    private const HERO_FIELDS = ['hero1', 'hero2', 'hero3'];

    private const OTHER_FOLDERS = [
        'carousel' => 'meu_app_uploads',
        'category' => 'meu_app_uploads',
    ];

    public function upload(Request $request): JsonResponse
    {
        $rule = 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120';

        $request->validate([
            'hero1'    => $rule,
            'hero2'    => $rule,
            'hero3'    => $rule,
            'carousel' => $rule,
            'category' => $rule,
        ]);

        try {
            $urls = [];

            // Hero: nome fixo (hero1..hero3) na pasta "carrossel", sobrescrevendo o anterior
            foreach (self::HERO_FIELDS as $field) {
                if ($request->hasFile($field)) {
                    $uploaded = Cloudinary::uploadApi()->upload(
                        $request->file($field)->getRealPath(),
                        [
                            'public_id'    => "carrossel/{$field}",
                            'asset_folder' => 'carrossel',
                            'overwrite'    => true,
                            'invalidate'   => true,
                        ]
                    );

                    $urls[$field] = [
                        'url'       => $uploaded['secure_url'],
                        'public_id' => $uploaded['public_id'],
                    ];

                    Cache::forget('carousel_slides');
                }
            }

            // Demais imagens: comportamento anterior
            foreach (self::OTHER_FOLDERS as $field => $folder) {
                if ($request->hasFile($field)) {
                    $uploaded = Cloudinary::uploadApi()->upload(
                        $request->file($field)->getRealPath(),
                        ['folder' => $folder]
                    );

                    $urls[$field] = [
                        'url'       => $uploaded['secure_url'],
                        'public_id' => $uploaded['public_id'],
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagens enviadas com sucesso!',
                'urls'    => $urls,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Falha ao enviar imagem para o Cloudinary.',
                'details' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    public function carousel(): JsonResponse
    {
        $slides = Cache::remember('carousel_slides', 600, function () {
            $result = Cloudinary::searchApi()
                ->expression('public_id:carrossel/hero*')
                ->sortBy('public_id', 'asc')
                ->maxResults(10)
                ->execute();

            return collect($result['resources'])->map(function ($r) {
                $base = $r['secure_url'];
                $transform = fn (string $t) =>
                    str_replace('/upload/', "/upload/{$t}/", $base);

                return [
                    'id'           => $r['public_id'],
                    'desktopImage' => $transform('f_auto,q_auto,c_fill,g_auto,ar_21:9,w_1920'),
                    'mobileImage'  => $transform('f_auto,q_auto,c_fill,g_auto,ar_25:26,w_800'),
                    'link'         => '/produtos',
                ];
            })->values();
        });

        return response()->json($slides);
    }
}