<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'perfil',
        'ativo',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acesso_em' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    public const PERFIS = [
        'administrador' => 'Administrador',
        'operador' => 'Operador',
    ];

    /**
     * O que cada perfil pode fazer, para exibição na tela de cadastro.
     */
    public const DESCRICAO_PERFIS = [
        'administrador' => 'Acesso total: altera tributos, custos fixos, dados da empresa e gerencia usuários.',
        'operador' => 'Forma e registra preços, cadastra produtos e categorias. Não altera a base do cálculo.',
    ];

    public function precificacoes(): HasMany
    {
        return $this->hasMany(Precificacao::class);
    }

    public function ehAdministrador(): bool
    {
        return $this->perfil === 'administrador';
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function getPerfilLabelAttribute(): string
    {
        return self::PERFIS[$this->perfil] ?? $this->perfil;
    }

    /**
     * Iniciais para o avatar do menu lateral.
     */
    public function getIniciaisAttribute(): string
    {
        $partes = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $primeira = mb_substr($partes[0] ?? '', 0, 1);
        $ultima = count($partes) > 1 ? mb_substr((string) end($partes), 0, 1) : '';

        return mb_strtoupper($primeira.$ultima);
    }
}
