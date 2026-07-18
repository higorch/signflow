<?php

namespace App\Support;

use Illuminate\Support\Str;

class AttachmentPath
{
    /**
     * Gera um caminho único para armazenamento de anexos.
     *
     * Exemplo:
     * covers/2026/06/29/01K1H4W5XW2T2QH4D7A4M8K2JY.webp
     */
    public static function make(string $directory, string $extension): string
    {
        $filename = (string) Str::ulid();

        return sprintf(
            '%s/%s/%s/%s/%s.%s',
            trim($directory, '/'),
            now()->format('Y'),
            now()->format('m'),
            now()->format('d'),
            $filename,
            ltrim($extension, '.')
        );
    }
}