<?php

namespace ToSend\Laravel;

use ToSend\Laravel\Contracts\ToSendClient;
use ToSend\Laravel\Data\Email;
use ToSend\Laravel\Data\EmailResponse;
use ToSend\Laravel\Data\BatchResponse;
use ToSend\Laravel\Data\AccountInfo;
use ToSend\Laravel\Exceptions\ToSendException;

class ToSend implements ToSendClient
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.tosend.com',
        int $timeout = 30
    ) {
        $this->apiKey = trim($apiKey);
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        if (empty($this->apiKey)) {
            throw new ToSendException('API key is required', 401);
        }
    }

    /**
     * Send a single email.
     *
     * @param array|Email $params Email parameters or Email object
     */
    public function send(array|Email $params): EmailResponse
    {
        $data = $params instanceof Email ? $params->toArray() : $params;

        $this->validateEmailParams($data);

        $response = $this->request('POST', '/v2/emails', $data);

        return EmailResponse::fromArray($response);
    }

    /**
     * Send multiple emails in a batch.
     *
     * @param array $emails Array of email parameters or Email objects
     */
    public function batch(array $emails): BatchResponse
    {
        if (empty($emails)) {
            throw new ToSendException('Emails array is required and cannot be empty', 422, [
                'emails' => ['required' => 'At least one email is required']
            ]);
        }

        $emailsData = [];
        foreach ($emails as $index => $email) {
            $data = $email instanceof Email ? $email->toArray() : $email;

            try {
                $this->validateEmailParams($data);
            } catch (ToSendException $e) {
                throw new ToSendException(
                    "Email at index {$index}: " . $e->getMessage(),
                    $e->getCode(),
                    $e->getErrors()
                );
            }

            $emailsData[] = $data;
        }

        $response = $this->request('POST', '/v2/emails/batch', ['emails' => $emailsData]);

        return BatchResponse::fromArray($response);
    }

    /**
     * Get account information.
     */
    public function getAccountInfo(): AccountInfo
    {
        $response = $this->request('GET', '/v2/info');

        return AccountInfo::fromArray($response);
    }

    /**
     * Create a new Email builder.
     */
    public function email(string|array $from, string $subject): Email
    {
        return Email::make($from, $subject);
    }

    /**
     * Validate email parameters.
     *
     * @throws ToSendException If validation fails
     */
    protected function validateEmailParams(array $params): void
    {
        $errors = [];

        // Validate 'from'
        if (empty($params['from'])) {
            $errors['from'] = ['required' => 'From is required'];
        } elseif (!is_array($params['from'])) {
            $errors['from'] = ['invalid' => 'From must be an array with email and optional name'];
        } elseif (empty($params['from']['email'])) {
            $errors['from'] = ['email_required' => 'From email is required'];
        } elseif (!filter_var($params['from']['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['from'] = ['email_invalid' => 'From email is invalid'];
        }

        // Validate 'to'
        if (empty($params['to'])) {
            $errors['to'] = ['required' => 'To is required'];
        } elseif (!is_array($params['to'])) {
            $errors['to'] = ['invalid' => 'To must be an array of recipients'];
        } else {
            $hasValidRecipient = false;
            foreach ($params['to'] as $recipient) {
                if (is_array($recipient) && !empty($recipient['email']) && filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                    $hasValidRecipient = true;
                    break;
                }
            }
            if (!$hasValidRecipient) {
                $errors['to'] = ['invalid' => 'At least one valid recipient email is required'];
            }
        }

        // Validate 'subject'
        if (empty($params['subject'])) {
            $errors['subject'] = ['required' => 'Subject is required'];
        }

        // Validate 'html' or 'text'
        if (empty($params['html']) && empty($params['text'])) {
            $errors['content'] = ['required' => 'Either html or text content is required'];
        }

        if (!empty($errors)) {
            $firstError = reset($errors);
            $message = is_array($firstError) ? reset($firstError) : $firstError;
            throw new ToSendException($message, 422, $errors);
        }
    }

    /**
     * Make an API request.
     *
     * @throws ToSendException
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new ToSendException('cURL error: ' . $error);
        }

        $decoded = json_decode($response, true) ?? [];

        if ($httpCode >= 400) {
            throw ToSendException::fromResponse($decoded, $httpCode);
        }

        return $decoded;
    }
}
