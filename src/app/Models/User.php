<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\Ulid;
use App\Traits\HasSecureHashes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
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
        'role', // root, admin, customer, signer,
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
            $builder->orderBy('ulid');
        });
    }
    
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // Lista os usuários internos vinculados ao customer. role=customer $customer->internalUsers()
    public function internalUsers()
    {
        return $this->belongsToMany(User::class, 'customer_users', 'customer_id', 'user_id');
    }

    // Lista os customers aos quais o usuário interno foi vinculado. $signer->linkedCustomers()
    public function linkedCustomers()
    {
        return $this->belongsToMany(User::class, 'customer_users', 'user_id', 'customer_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function processes()
    {
        return $this->hasMany(Process::class, 'owner_id');
    }

    public function processSigners()
    {
        return $this->hasMany(ProcessSigner::class);
    }

    public function avatar()
    {
        return $this->morphOne(Attachment::class, 'attachable')->where('taxonomy', 'user-avatar')->where('status', 'active');
    }

    public function getDisplayNameAttribute(): string
    {
        return filled($this->nickname) ? $this->nickname : $this->name;
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
