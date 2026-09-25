<?php

namespace ToSend\Laravel\Data;

/**
 * GET /v2/info with a sending key.
 */
class AccountInfo
{
    /**
     * @param Domain[] $domains
     */
    public function __construct(
        public readonly int $id,
        public readonly string $status,
        public readonly string $planType,
        /** Remaining credits. Null means the account does not consume credits. */
        public readonly ?int $creditBalance,
        public readonly int $monthlyEmailLimit,
        public readonly int $limitPerSecond,
        public readonly string $sesRegion,
        /** Fraction between 0 and 1, not a percentage. */
        public readonly float $bounceRate,
        /** Fraction between 0 and 1, not a percentage. */
        public readonly float $complaintRate,
        public readonly string $sesStatus,
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
            id: (int) ($account['id'] ?? 0),
            status: (string) ($account['status'] ?? ''),
            planType: (string) ($account['plan_type'] ?? ''),
            creditBalance: isset($account['credit_balance']) ? (int) $account['credit_balance'] : null,
            monthlyEmailLimit: (int) ($account['monthly_email_limit'] ?? 0),
            limitPerSecond: (int) ($account['limit_per_second'] ?? 0),
            sesRegion: (string) ($account['ses_region'] ?? ''),
            bounceRate: (float) ($account['bounce_rate'] ?? 0),
            complaintRate: (float) ($account['complaint_rate'] ?? 0),
            sesStatus: (string) ($account['ses_status'] ?? ''),
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
        return array_values(array_filter($this->domains, fn($d) => $d->isVerified()));
    }

    public function toArray(): array
    {
        return [
            'account' => [
                'id' => $this->id,
                'status' => $this->status,
                'plan_type' => $this->planType,
                'credit_balance' => $this->creditBalance,
                'monthly_email_limit' => $this->monthlyEmailLimit,
                'limit_per_second' => $this->limitPerSecond,
                'ses_region' => $this->sesRegion,
                'bounce_rate' => $this->bounceRate,
                'complaint_rate' => $this->complaintRate,
                'ses_status' => $this->sesStatus,
            ],
            'domains' => array_map(fn($d) => $d->toArray(), $this->domains),
        ];
    }
}
