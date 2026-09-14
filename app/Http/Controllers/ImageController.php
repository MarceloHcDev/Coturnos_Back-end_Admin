<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ImageController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'banner'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'carousel' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'category' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        try {
            $urls = [];

            foreach (['banner', 'carousel', 'category'] as $field) {
                if ($request->hasFile($field)) {
                    $uploaded = Cloudinary::uploadApi()->upload(
                        $request->file($field)->getRealPath(),
                        ['folder' => 'meu_app_uploads']
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
}