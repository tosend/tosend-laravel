<?php

namespace ToSend\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ToSend\Laravel\Data\AccountInfo;
use ToSend\Laravel\Data\Domain;

class AccountInfoTest extends TestCase
{
    /** The exact shape GET /v2/info returns for a sending key. */
    private function realResponse(): array
    {
        return [
            'account' => [
                'id' => 142,
                'status' => 'active',
                'plan_type' => 'pro',
                'credit_balance' => 48750,
                'monthly_email_limit' => 100000,
                'limit_per_second' => 14,
                'ses_region' => 'us-east-1',
                'bounce_rate' => 0.012,
                'complaint_rate' => 0.0003,
                'ses_status' => 'healthy',
            ],
            'domains' => [
                [
                    'id' => 88,
                    'domain_name' => 'acme.com',
                    'verification_status' => 'verified',
                    'is_default' => 1,
                    'total_sent' => 24189,
                    'total_bounced' => 212,
                    'total_complained' => 7,
                ],
                [
                    'id' => 112,
                    'domain_name' => 'mail.acme.com',
                    'verification_status' => 'pending',
                    'is_default' => 0,
                    'total_sent' => 0,
                    'total_bounced' => 0,
                    'total_complained' => 0,
                ],
            ],
        ];
    }

    public function test_creates_from_the_real_response(): void
    {
        $info = AccountInfo::fromArray($this->realResponse());

        $this->assertSame(142, $info->id);
        $this->assertSame('active', $info->status);
        $this->assertSame('pro', $info->planType);
        $this->assertSame(48750, $info->creditBalance);
        $this->assertSame(100000, $info->monthlyEmailLimit);
        $this->assertSame(14, $info->limitPerSecond);
        $this->assertSame('us-east-1', $info->sesRegion);
        $this->assertSame(0.012, $info->bounceRate);
        $this->assertSame(0.0003, $info->complaintRate);
        $this->assertSame('healthy', $info->sesStatus);
        $this->assertCount(2, $info->domains);

        $domain = $info->domains[0];
        $this->assertSame(88, $domain->id);
        $this->assertSame('acme.com', $domain->domainName);
        $this->assertTrue($domain->isDefault);
        $this->assertSame(24189, $domain->totalSent);
        $this->assertSame(212, $domain->totalBounced);
        $this->assertSame(7, $domain->totalComplained);
        $this->assertFalse($info->domains[1]->isDefault);
    }

    public function test_null_credit_balance_means_unlimited(): void
    {
        $data = $this->realResponse();
        $data['account']['credit_balance'] = null;

        $this->assertNull(AccountInfo::fromArray($data)->creditBalance);
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
        $info = AccountInfo::fromArray($this->realResponse());

        $verified = $info->verifiedDomains();

        $this->assertCount(1, $verified);
        $this->assertSame('acme.com', $verified[0]->domainName);
    }

    public function test_to_array_round_trips(): void
    {
        $data = $this->realResponse();
        $data['domains'][0]['is_default'] = true;
        $data['domains'][1]['is_default'] = false;

        $this->assertEquals($data, AccountInfo::fromArray($this->realResponse())->toArray());
    }
}

class DomainTest extends TestCase
{
    public function test_creates_from_array(): void
    {
        $domain = Domain::fromArray([
            'id' => 5,
            'domain_name' => 'example.com',
            'verification_status' => 'verified',
            'is_default' => 0,
            'total_sent' => 3,
            'total_bounced' => 1,
            'total_complained' => 0,
        ]);

        $this->assertSame(5, $domain->id);
        $this->assertSame('example.com', $domain->domainName);
        $this->assertSame('verified', $domain->verificationStatus);
        $this->assertFalse($domain->isDefault);
        $this->assertSame(3, $domain->totalSent);
    }

    public function test_is_verified(): void
    {
        $domain = new Domain(1, 'example.com', 'verified');

        $this->assertTrue($domain->isVerified());
        $this->assertFalse($domain->isPending());
    }

    public function test_is_pending(): void
    {
        $domain = new Domain(1, 'example.com', 'pending');

        $this->assertFalse($domain->isVerified());
        $this->assertTrue($domain->isPending());
    }
}
