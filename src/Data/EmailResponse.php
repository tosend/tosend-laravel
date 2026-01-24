<?php

namespace ToSend\Laravel\Data;

class EmailResponse
{
    public function __construct(
        public readonly string $messageId
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            messageId: $data['message_id'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'message_id' => $this->messageId,
        ];
    }
}
