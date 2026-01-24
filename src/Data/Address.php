<?php

namespace ToSend\Laravel\Data;

use Illuminate\Contracts\Support\Arrayable;

class Address implements Arrayable
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $name = null
    ) {}

    /**
     * Create from string email or array.
     */
    public static function make(string|array $address): self
    {
        if (is_string($address)) {
            return new self(email: $address);
        }

        return new self(
            email: $address['email'] ?? $address['address'] ?? '',
            name: $address['name'] ?? null
        );
    }

    /**
     * Create multiple addresses from array.
     *
     * @return self[]
     */
    public static function makeMany(array $addresses): array
    {
        return array_map(fn($address) => self::make($address), $addresses);
    }

    public function toArray(): array
    {
        $data = ['email' => $this->email];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        return $data;
    }

    public function toString(): string
    {
        if ($this->name) {
            return "{$this->name} <{$this->email}>";
        }

        return $this->email;
    }
}
