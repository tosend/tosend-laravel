<?php

namespace ToSend\Laravel\Data;

use Illuminate\Contracts\Support\Arrayable;

class Attachment implements Arrayable
{
    public function __construct(
        public readonly string $content,
        public readonly string $name,
        public readonly string $type = 'application/octet-stream'
    ) {}

    /**
     * Create attachment from file path.
     */
    public static function fromPath(string $path, ?string $name = null, ?string $type = null): self
    {
        $content = base64_encode(file_get_contents($path));
        $name = $name ?? basename($path);
        $type = $type ?? mime_content_type($path) ?: 'application/octet-stream';

        return new self(
            content: $content,
            name: $name,
            type: $type
        );
    }

    /**
     * Create attachment from raw content.
     */
    public static function fromContent(string $content, string $name, string $type = 'application/octet-stream'): self
    {
        return new self(
            content: base64_encode($content),
            name: $name,
            type: $type
        );
    }

    /**
     * Create attachment from base64 encoded content.
     */
    public static function fromBase64(string $base64Content, string $name, string $type = 'application/octet-stream'): self
    {
        return new self(
            content: $base64Content,
            name: $name,
            type: $type
        );
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'name' => $this->name,
            'type' => $this->type,
        ];
    }
}
