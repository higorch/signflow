<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\HasSecureHashes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Process extends Model
{
    use HasUlids, HasSecureHashes;

    protected array $ulidColumns = ['ulid'];

    /** Essas colunas são hash para pesquisa */
    protected array $hashedColumns = [
        'title_hash' => 'column:title',
        'description_hash' => 'column:description',
    ];

    protected $fillable = [
        'owner_id',
        'category_id',
        'reference',
        'title',
        'description',
        'status', // draft, awaiting-approval, approved, failed, canceled
    ];

    protected $hidden = [
        'title_hash',
        'description_hash'
    ];

    protected function casts(): array
    {
        return [
            // publicos protegidos
            'title' => 'encrypted',
            'description' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('order_by_id', function (Builder $builder) {
            $builder->orderBy('id');
        });
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function signers()
    {
        return $this->hasMany(ProcessSigner::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function media()
    {
        return $this->morphMany(Attachment::class, 'attachable')->where('taxonomy', 'process-file')->where('status', 'active');
    }

    public function scopeOwnedBy(Builder $query, int|string $ownerId): Builder
    {
        return $query->where('owner_id', $ownerId);
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
