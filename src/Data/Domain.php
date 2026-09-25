<?php

namespace ToSend\Laravel\Data;

/**
 * A domain as listed by GET /v2/info.
 */
class Domain
{
    public function __construct(
        public readonly int $id,
        public readonly string $domainName,
        public readonly string $verificationStatus,
        public readonly bool $isDefault = false,
        public readonly int $totalSent = 0,
        public readonly int $totalBounced = 0,
        public readonly int $totalComplained = 0
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            domainName: (string) ($data['domain_name'] ?? ''),
            verificationStatus: (string) ($data['verification_status'] ?? ''),
            isDefault: (bool) ($data['is_default'] ?? false),
            totalSent: (int) ($data['total_sent'] ?? 0),
            totalBounced: (int) ($data['total_bounced'] ?? 0),
            totalComplained: (int) ($data['total_complained'] ?? 0)
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
            'id' => $this->id,
            'domain_name' => $this->domainName,
            'verification_status' => $this->verificationStatus,
            'is_default' => $this->isDefault,
            'total_sent' => $this->totalSent,
            'total_bounced' => $this->totalBounced,
            'total_complained' => $this->totalComplained,
        ];
    }
}
