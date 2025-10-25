<?php

namespace App\Models\Cassandra;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use JsonSerializable;

abstract class CassandraModel implements Arrayable, JsonSerializable
{
    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function from(array $payload): static
    {
        return new static($payload);
    }

    public function fill(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->attributes, $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

