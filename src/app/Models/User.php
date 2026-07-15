<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\Ulid;
use App\Traits\HasSecureHashes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Ulid, HasSecureHashes;

    protected array $ulidColumns = ['ulid'];

    /** Essas colunas são hash para pesquisa */
    protected array $hashedColumns = [
        'name_hash' => 'column:name',
        'email_hash' => 'column:email',
        'role_hash' => 'column:role',
        'cpf_cnpj_hash' => 'column:cpf_cnpj|sanitize',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'department_id',
        'name',
        'email',
        'password',
        'status',
        'cpf_cnpj',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'name_hash',
        'email_hash',
        'role_hash',
        'cpf_cnpj_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // publicos protegidos
            'name' => 'encrypted',
            'email' => 'encrypted',

            // privados sensiveis
            'role' => 'encrypted',
            'cpf_cnpj' => 'encrypted',

            // sistema
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function processes()
    {
        return $this->hasMany(Process::class);
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->hotFeedAvatar ?? $this->companionAvatar,
        );
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
