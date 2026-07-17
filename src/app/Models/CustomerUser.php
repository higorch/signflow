<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class CustomerUser extends Model
{
    use HasUlids;

    protected $fillable = [
        'customer_id',
        'user_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('order_by_id', function (Builder $builder) {
            $builder->orderBy('id');
        });
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
