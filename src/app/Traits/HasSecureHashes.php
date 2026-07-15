<?php

namespace App\Traits;

trait HasSecureHashes
{
    protected static function bootHasSecureHashes(): void
    {
        static::saving(function ($model) {
            if (!property_exists($model, 'hashedColumns') || !is_array($model->hashedColumns)) return;

            foreach ($model->hashedColumns as $hashColumn => $config) {
                $parts = explode('|', $config);
                $options = [];

                foreach ($parts as $part) {
                    if (str_contains($part, ':')) {
                        [$key, $value] = explode(':', $part, 2);
                        $options[$key] = $value;
                    } else {
                        $options[$part] = true;
                    }
                }

                $column = $options['column'] ?? null;

                if (!$column || !$model->isDirty($column)) continue;

                $model->{$hashColumn} = hmac_hash(
                    $model->{$column},
                    $options['sanitize'] ?? false,
                    $options['remove_spaces'] ?? false
                );
            }
        });
    }
}