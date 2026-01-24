<?php

namespace ToSend\Laravel\Data;

class BatchResponse
{
    /**
     * @param BatchEmailResult[] $results
     */
    public function __construct(
        public readonly array $results
    ) {}

    public static function fromArray(array $data): self
    {
        $results = array_map(
            fn($result) => BatchEmailResult::fromArray($result),
            $data['results'] ?? []
        );

        return new self(results: $results);
    }

    /**
     * Get all successful results.
     *
     * @return BatchEmailResult[]
     */
    public function successful(): array
    {
        return array_filter($this->results, fn($r) => $r->isSuccess());
    }

    /**
     * Get all failed results.
     *
     * @return BatchEmailResult[]
     */
    public function failed(): array
    {
        return array_filter($this->results, fn($r) => !$r->isSuccess());
    }

    /**
     * Check if all emails were sent successfully.
     */
    public function allSuccessful(): bool
    {
        return count($this->failed()) === 0;
    }

    /**
     * Get the count of successful emails.
     */
    public function successCount(): int
    {
        return count($this->successful());
    }

    /**
     * Get the count of failed emails.
     */
    public function failedCount(): int
    {
        return count($this->failed());
    }

    public function toArray(): array
    {
        return [
            'results' => array_map(fn($r) => $r->toArray(), $this->results),
        ];
    }
}

class BatchEmailResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $messageId = null,
        public readonly ?string $message = null,
        public readonly array $errors = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? 'error',
            messageId: $data['message_id'] ?? null,
            message: $data['message'] ?? null,
            errors: $data['errors'] ?? []
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isError(): bool
    {
        return $this->status === 'error';
    }

    public function isSpam(): bool
    {
        return $this->status === 'spam';
    }

    public function toArray(): array
    {
        $data = ['status' => $this->status];

        if ($this->messageId !== null) {
            $data['message_id'] = $this->messageId;
        }

        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        if (!empty($this->errors)) {
            $data['errors'] = $this->errors;
        }

        return $data;
    }
}
