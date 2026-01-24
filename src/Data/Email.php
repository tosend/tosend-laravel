<?php

namespace ToSend\Laravel\Data;

use Illuminate\Contracts\Support\Arrayable;

class Email implements Arrayable
{
    /** @var Address[] */
    public array $to = [];

    /** @var Address[] */
    public array $cc = [];

    /** @var Address[] */
    public array $bcc = [];

    /** @var Attachment[] */
    public array $attachments = [];

    public array $headers = [];

    public function __construct(
        public readonly Address $from,
        public readonly string $subject,
        public readonly ?string $html = null,
        public readonly ?string $text = null,
        public readonly ?Address $replyTo = null
    ) {}

    /**
     * Create a new email.
     */
    public static function make(
        string|array $from,
        string $subject,
        ?string $html = null,
        ?string $text = null
    ): self {
        return new self(
            from: Address::make($from),
            subject: $subject,
            html: $html,
            text: $text
        );
    }

    /**
     * Add recipients.
     */
    public function to(string|array ...$recipients): self
    {
        foreach ($recipients as $recipient) {
            if (is_array($recipient) && isset($recipient[0])) {
                // Array of recipients
                foreach ($recipient as $r) {
                    $this->to[] = Address::make($r);
                }
            } else {
                $this->to[] = Address::make($recipient);
            }
        }

        return $this;
    }

    /**
     * Add CC recipients.
     */
    public function cc(string|array ...$recipients): self
    {
        foreach ($recipients as $recipient) {
            if (is_array($recipient) && isset($recipient[0])) {
                foreach ($recipient as $r) {
                    $this->cc[] = Address::make($r);
                }
            } else {
                $this->cc[] = Address::make($recipient);
            }
        }

        return $this;
    }

    /**
     * Add BCC recipients.
     */
    public function bcc(string|array ...$recipients): self
    {
        foreach ($recipients as $recipient) {
            if (is_array($recipient) && isset($recipient[0])) {
                foreach ($recipient as $r) {
                    $this->bcc[] = Address::make($r);
                }
            } else {
                $this->bcc[] = Address::make($recipient);
            }
        }

        return $this;
    }

    /**
     * Set reply-to address.
     */
    public function replyTo(string|array $address): self
    {
        $clone = clone $this;

        return new self(
            from: $this->from,
            subject: $this->subject,
            html: $this->html,
            text: $this->text,
            replyTo: Address::make($address)
        );
    }

    /**
     * Add an attachment.
     */
    public function attach(Attachment $attachment): self
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * Attach a file from path.
     */
    public function attachFromPath(string $path, ?string $name = null, ?string $type = null): self
    {
        $this->attachments[] = Attachment::fromPath($path, $name, $type);

        return $this;
    }

    /**
     * Add a custom header.
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Add multiple headers.
     */
    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'from' => $this->from->toArray(),
            'to' => array_map(fn(Address $a) => $a->toArray(), $this->to),
            'subject' => $this->subject,
        ];

        if ($this->html !== null) {
            $data['html'] = $this->html;
        }

        if ($this->text !== null) {
            $data['text'] = $this->text;
        }

        if ($this->replyTo !== null) {
            $data['reply_to'] = $this->replyTo->toArray();
        }

        if (!empty($this->cc)) {
            $data['cc'] = array_map(fn(Address $a) => $a->toArray(), $this->cc);
        }

        if (!empty($this->bcc)) {
            $data['bcc'] = array_map(fn(Address $a) => $a->toArray(), $this->bcc);
        }

        if (!empty($this->attachments)) {
            $data['attachments'] = array_map(fn(Attachment $a) => $a->toArray(), $this->attachments);
        }

        if (!empty($this->headers)) {
            $data['headers'] = $this->headers;
        }

        return $data;
    }
}
