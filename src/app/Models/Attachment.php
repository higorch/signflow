<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Attachment extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'parent_attachment_id',
        'attachable_type',
        'attachable_id',
        'disk',
        'path',
        'extension',
        'size',
        'caption',
        'taxonomy',
        'status',
        'sort',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('order_by_id', function (Builder $builder) {
            $builder->orderBy('id');
        });
    }

    public function attachable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_attachment_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_attachment_id');
    }

    public function getCreatedAtAttribute(?string $value)
    {
        return $value ? Carbon::parse($value)->timezone('America/Sao_Paulo') : null;
    }

    public function getUpdatedAtAttribute(?string $value)
    {
        return $value ? Carbon::parse($value)->timezone('America/Sao_Paulo') : null;
    }
}
