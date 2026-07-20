<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ProcessSigner extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'process_id',
        'status',
        'action_at',
        'action_ip',
        'action_agent',
        'rejection_reason',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'action_at' => 'datetime',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('order_by_id', function (Builder $builder) {
            $builder->orderBy('id');
        });
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActionAtAttribute(?string $value)
    {
        return $value ? Carbon::parse($value)->timezone('America/Sao_Paulo') : null;
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
