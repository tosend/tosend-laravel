<?php

namespace ToSend\Laravel\Mail;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;
use ToSend\Laravel\Contracts\ToSendClient;
use ToSend\Laravel\Data\Attachment;
use ToSend\Laravel\Exceptions\ToSendException;

class ToSendTransport extends AbstractTransport
{
    protected ToSendClient $client;
    protected array $defaultFrom;

    public function __construct(ToSendClient $client, array $defaultFrom = [])
    {
        parent::__construct();

        $this->client = $client;
        $this->defaultFrom = $defaultFrom;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $payload = $this->buildPayload($email);

        try {
            $response = $this->client->send($payload);

            // Set the message ID from the response
            $message->getOriginalMessage()->getHeaders()->addTextHeader(
                'X-ToSend-Message-Id',
                $response->messageId
            );
        } catch (ToSendException $e) {
            throw new \Symfony\Component\Mailer\Exception\TransportException(
                'ToSend API error: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    protected function buildPayload(Email $email): array
    {
        $payload = [
            'from' => $this->getFrom($email),
            'to' => $this->formatAddresses($email->getTo()),
            'subject' => $email->getSubject() ?? '',
        ];

        // Add HTML content
        if ($html = $email->getHtmlBody()) {
            $payload['html'] = $html;
        }

        // Add text content
        if ($text = $email->getTextBody()) {
            $payload['text'] = $text;
        }

        // Add CC
        if ($cc = $email->getCc()) {
            $payload['cc'] = $this->formatAddresses($cc);
        }

        // Add BCC
        if ($bcc = $email->getBcc()) {
            $payload['bcc'] = $this->formatAddresses($bcc);
        }

        // Add Reply-To
        if ($replyTo = $email->getReplyTo()) {
            $addresses = $this->formatAddresses($replyTo);
            if (!empty($addresses)) {
                $payload['reply_to'] = $addresses[0];
            }
        }

        // Add attachments
        $attachments = $this->getAttachments($email);
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        // Add custom headers
        $headers = $this->getCustomHeaders($email);
        if (!empty($headers)) {
            $payload['headers'] = $headers;
        }

        return $payload;
    }

    protected function getFrom(Email $email): array
    {
        $from = $email->getFrom();

        if (!empty($from)) {
            $address = $from[0];
            return [
                'email' => $address->getAddress(),
                'name' => $address->getName() ?: null,
            ];
        }

        // Use default from
        return [
            'email' => $this->defaultFrom['address'] ?? '',
            'name' => $this->defaultFrom['name'] ?? null,
        ];
    }

    /**
     * @param Address[] $addresses
     */
    protected function formatAddresses(array $addresses): array
    {
        return array_map(function (Address $address) {
            $data = ['email' => $address->getAddress()];

            if ($name = $address->getName()) {
                $data['name'] = $name;
            }

            return $data;
        }, $addresses);
    }

    protected function getAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            if ($attachment instanceof DataPart) {
                $attachments[] = [
                    'name' => $attachment->getFilename() ?? 'attachment',
                    'type' => $attachment->getContentType() ?? 'application/octet-stream',
                    'content' => base64_encode($attachment->getBody()),
                ];
            }
        }

        return $attachments;
    }

    protected function getCustomHeaders(Email $email): array
    {
        $headers = [];
        $skipHeaders = [
            'from', 'to', 'cc', 'bcc', 'subject', 'reply-to',
            'content-type', 'mime-version', 'date', 'message-id',
        ];

        foreach ($email->getHeaders()->all() as $header) {
            $name = strtolower($header->getName());

            if (!in_array($name, $skipHeaders) && !str_starts_with($name, 'x-')) {
                continue;
            }

            if (str_starts_with($name, 'x-')) {
                $headers[$header->getName()] = $header->getBodyAsString();
            }
        }

        return $headers;
    }

    public function __toString(): string
    {
        return 'tosend';
    }
}
