<?php

namespace App\Support;

use Intervention\Image\Alignment;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;

class ImageWatermark
{
    public static function apply(
        ImageInterface $image,
        Alignment $alignment = Alignment::CENTER,
        float $transparency = 0.5,
        float $width = 0.15,
        int $offsetX = 0,
        int $offsetY = 0,
    ): ImageInterface {
        $watermarkPath = resource_path('assets/images/watermark.png');

        if (! file_exists($watermarkPath)) {
            return $image;
        }

        $watermark = Image::decode($watermarkPath);

        $watermark->scale(
            width: (int) round($image->width() * $width)
        );

        $image->insert(
            $watermark,
            x: $offsetX,
            y: $offsetY,
            alignment: $alignment,
            transparency: $transparency,
        );

        return $image;
    }
}
