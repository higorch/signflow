<?php

namespace App\Support;

use Intervention\Image\Alignment;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;

class ImageWatermark
{
    public static function apply(ImageInterface $image): ImageInterface
    {
        $watermarkPath = resource_path('assets/images/watermark.png');

        if (! file_exists($watermarkPath)) return $image;

        $watermark = Image::decode($watermarkPath);

        $watermark->scale(width: 160);
        $watermark->grayscale();
        $watermark->brightness(100);
        $watermark->contrast(100);

        $image->insert($watermark, x: 0, y: 0, alignment: Alignment::CENTER, transparency: 0.5);

        return $image;
    }
}