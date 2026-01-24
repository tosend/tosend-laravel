<?php

namespace ToSend\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ToSend\Laravel\Data\Address;

class AddressTest extends TestCase
{
    public function test_creates_address_with_email_only(): void
    {
        $address = new Address('john@example.com');

        $this->assertEquals('john@example.com', $address->email);
        $this->assertNull($address->name);
    }

    public function test_creates_address_with_email_and_name(): void
    {
        $address = new Address('john@example.com', 'John Doe');

        $this->assertEquals('john@example.com', $address->email);
        $this->assertEquals('John Doe', $address->name);
    }

    public function test_make_from_string(): void
    {
        $address = Address::make('john@example.com');

        $this->assertEquals('john@example.com', $address->email);
        $this->assertNull($address->name);
    }

    public function test_make_from_array_with_email_key(): void
    {
        $address = Address::make([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $this->assertEquals('john@example.com', $address->email);
        $this->assertEquals('John Doe', $address->name);
    }

    public function test_make_from_array_with_address_key(): void
    {
        $address = Address::make([
            'address' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $this->assertEquals('john@example.com', $address->email);
        $this->assertEquals('John Doe', $address->name);
    }

    public function test_make_many_creates_multiple_addresses(): void
    {
        $addresses = Address::makeMany([
            'john@example.com',
            ['email' => 'jane@example.com', 'name' => 'Jane'],
        ]);

        $this->assertCount(2, $addresses);
        $this->assertEquals('john@example.com', $addresses[0]->email);
        $this->assertEquals('jane@example.com', $addresses[1]->email);
        $this->assertEquals('Jane', $addresses[1]->name);
    }

    public function test_to_array_with_email_only(): void
    {
        $address = new Address('john@example.com');

        $this->assertEquals(['email' => 'john@example.com'], $address->toArray());
    }

    public function test_to_array_with_name(): void
    {
        $address = new Address('john@example.com', 'John Doe');

        $this->assertEquals([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ], $address->toArray());
    }

    public function test_to_string_with_email_only(): void
    {
        $address = new Address('john@example.com');

        $this->assertEquals('john@example.com', $address->toString());
    }

    public function test_to_string_with_name(): void
    {
        $address = new Address('john@example.com', 'John Doe');

        $this->assertEquals('John Doe <john@example.com>', $address->toString());
    }
}
