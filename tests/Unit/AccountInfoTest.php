<?php

namespace ToSend\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ToSend\Laravel\Data\AccountInfo;
use ToSend\Laravel\Data\Domain;

class AccountInfoTest extends TestCase
{
    public function test_creates_from_array(): void
    {
        $info = AccountInfo::fromArray([
            'account' => [
                'title' => 'My Account',
                'plan_type' => 'pro',
                'status' => 'active',
                'emails_usage_this_month' => 1000,
                'emails_sent_last_24hrs' => 50,
            ],
            'domains' => [
                [
                    'domain_name' => 'example.com',
                    'verification_status' => 'verified',
                    'created_at' => '2024-01-01 00:00:00',
                ],
            ],
        ]);

        $this->assertEquals('My Account', $info->title);
        $this->assertEquals('pro', $info->planType);
        $this->assertEquals('active', $info->status);
        $this->assertEquals(1000, $info->emailsUsageThisMonth);
        $this->assertEquals(50, $info->emailsSentLast24Hours);
        $this->assertCount(1, $info->domains);
    }

    public function test_is_active(): void
    {
        $info = AccountInfo::fromArray([
            'account' => ['status' => 'active'],
            'domains' => [],
        ]);

        $this->assertTrue($info->isActive());
    }

    public function test_is_not_active(): void
    {
        $info = AccountInfo::fromArray([
            'account' => ['status' => 'suspended'],
            'domains' => [],
        ]);

        $this->assertFalse($info->isActive());
    }

    public function test_verified_domains(): void
    {
        $info = AccountInfo::fromArray([
            'account' => [],
            'domains' => [
                ['domain_name' => 'verified.com', 'verification_status' => 'verified', 'created_at' => ''],
                ['domain_name' => 'pending.com', 'verification_status' => 'pending', 'created_at' => ''],
                ['domain_name' => 'also-verified.com', 'verification_status' => 'verified', 'created_at' => ''],
            ],
        ]);

        $verified = $info->verifiedDomains();

        $this->assertCount(2, $verified);
    }

    public function test_to_array(): void
    {
        $info = new AccountInfo(
            title: 'Test',
            planType: 'free',
            status: 'active',
            emailsUsageThisMonth: 100,
            emailsSentLast24Hours: 10,
            domains: []
        );

        $array = $info->toArray();

        $this->assertArrayHasKey('account', $array);
        $this->assertArrayHasKey('domains', $array);
        $this->assertEquals('Test', $array['account']['title']);
    }
}

class DomainTest extends TestCase
{
    public function test_creates_from_array(): void
    {
        $domain = Domain::fromArray([
            'domain_name' => 'example.com',
            'verification_status' => 'verified',
            'created_at' => '2024-01-01 00:00:00',
        ]);

        $this->assertEquals('example.com', $domain->domainName);
        $this->assertEquals('verified', $domain->verificationStatus);
        $this->assertEquals('2024-01-01 00:00:00', $domain->createdAt);
    }

    public function test_is_verified(): void
    {
        $domain = new Domain('example.com', 'verified', '');

        $this->assertTrue($domain->isVerified());
        $this->assertFalse($domain->isPending());
    }

    public function test_is_pending(): void
    {
        $domain = new Domain('example.com', 'pending', '');

        $this->assertFalse($domain->isVerified());
        $this->assertTrue($domain->isPending());
    }

    public function test_to_array(): void
    {
        $domain = new Domain('example.com', 'verified', '2024-01-01');

        $this->assertEquals([
            'domain_name' => 'example.com',
            'verification_status' => 'verified',
            'created_at' => '2024-01-01',
        ], $domain->toArray());
    }
}
