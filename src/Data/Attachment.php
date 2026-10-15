<?php

namespace ToSend\Laravel\Data;

use Illuminate\Contracts\Support\Arrayable;

class Attachment implements Arrayable
{
    public function __construct(
        public readonly string $content,
        public readonly string $name,
        public readonly string $type = 'application/octet-stream',
        /** Set to embed inline: reference it in the HTML as cid:<contentId>. */
        public readonly ?string $contentId = null
    ) {}

    /**
     * Create attachment from file path.
     */
    public static function fromPath(string $path, ?string $name = null, ?string $type = null, ?string $contentId = null): self
    {
        $content = base64_encode(file_get_contents($path));
        $name = $name ?? basename($path);
        $type = $type ?? mime_content_type($path) ?: 'application/octet-stream';

        return new self(
            content: $content,
            name: $name,
            type: $type,
            contentId: $contentId
        );
    }

    /**
     * Create attachment from raw content.
     */
    public static function fromContent(string $content, string $name, string $type = 'application/octet-stream', ?string $contentId = null): self
    {
        return new self(
            content: base64_encode($content),
            name: $name,
            type: $type,
            contentId: $contentId
        );
    }

    /**
     * Create attachment from base64 encoded content.
     */
    public static function fromBase64(string $base64Content, string $name, string $type = 'application/octet-stream', ?string $contentId = null): self
    {
        return new self(
            content: $base64Content,
            name: $name,
            type: $type,
            contentId: $contentId
        );
    }

    public function toArray(): array
    {
        $data = [
            'content' => $this->content,
            'name' => $this->name,
            'type' => $this->type,
        ];

        if ($this->contentId !== null) {
            $data['content_id'] = $this->contentId;
        }

        return $data;
    }
}
