<?php

namespace ToSend\Laravel\Data;

class AccountInfo
{
    /**
     * @param Domain[] $domains
     */
    public function __construct(
        public readonly string $title,
        public readonly string $planType,
        public readonly string $status,
        public readonly int $emailsUsageThisMonth,
        public readonly int $emailsSentLast24Hours,
        public readonly array $domains
    ) {}

    public static function fromArray(array $data): self
    {
        $account = $data['account'] ?? [];
        $domains = array_map(
            fn($d) => Domain::fromArray($d),
            $data['domains'] ?? []
        );

        return new self(
            title: $account['title'] ?? '',
            planType: $account['plan_type'] ?? '',
            status: $account['status'] ?? '',
            emailsUsageThisMonth: $account['emails_usage_this_month'] ?? 0,
            emailsSentLast24Hours: $account['emails_sent_last_24hrs'] ?? 0,
            domains: $domains
        );
    }

    /**
     * Check if the account is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get verified domains.
     *
     * @return Domain[]
     */
    public function verifiedDomains(): array
    {
        return array_filter($this->domains, fn($d) => $d->isVerified());
    }

    public function toArray(): array
    {
        return [
            'account' => [
                'title' => $this->title,
                'plan_type' => $this->planType,
                'status' => $this->status,
                'emails_usage_this_month' => $this->emailsUsageThisMonth,
                'emails_sent_last_24hrs' => $this->emailsSentLast24Hours,
            ],
            'domains' => array_map(fn($d) => $d->toArray(), $this->domains),
        ];
    }
}

class Domain
{
    public function __construct(
        public readonly string $domainName,
        public readonly string $verificationStatus,
        public readonly string $createdAt
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            domainName: $data['domain_name'] ?? '',
            verificationStatus: $data['verification_status'] ?? '',
            createdAt: $data['created_at'] ?? ''
        );
    }

    public function isVerified(): bool
    {
        return $this->verificationStatus === 'verified';
    }

    public function isPending(): bool
    {
        return $this->verificationStatus === 'pending';
    }

    public function toArray(): array
    {
        return [
            'domain_name' => $this->domainName,
            'verification_status' => $this->verificationStatus,
            'created_at' => $this->createdAt,
        ];
    }
}
