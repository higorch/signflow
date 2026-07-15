<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

trait Ulid
{
    protected static function bootUlid()
    {
        static::creating(function (Model $model) {
            if (!isset($model->ulidColumns) || !is_array($model->ulidColumns) || empty($model->ulidColumns)) {
                throw new RuntimeException("O modelo " . get_class($model) . " deve definir a propriedade protected \$ulidColumns como um array de colunas ULID.");
            }

            foreach ($model->ulidColumns as $column) {
                if (empty($model->{$column})) {
                    $model->{$column} = strtolower((string) Str::ulid());
                }
            }
        });
    }
}