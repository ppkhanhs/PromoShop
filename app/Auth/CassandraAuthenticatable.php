<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

class CassandraAuthenticatable implements Authenticatable
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(protected array $attributes = [])
    {
    }

    public function getAuthIdentifierName(): string
    {
        return 'user_id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->attributes[$this->getAuthIdentifierName()] ?? null;
    }

    public function getAuthPassword(): string
    {
        return (string) ($this->attributes['password_hash'] ?? $this->attributes['password'] ?? '');
    }

    public function getRememberToken(): ?string
    {
        return $this->attributes['remember_token'] ?? null;
    }

    public function setRememberToken($value): void
    {
        $this->attributes['remember_token'] = $value;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
