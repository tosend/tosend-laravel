<?php

namespace ToSend\Laravel\Concerns;

use ToSend\Laravel\Exceptions\ToSendException;

/**
 * The HTTP plumbing shared by the sending client and the admin client.
 */
trait MakesHttpRequests
{
    /**
     * Perform one request and decode its JSON body.
     *
     * @param  array<int, string> $headers
     * @param  array|null         $body    JSON-encoded when not null
     * @throws ToSendException
     */
    protected function performHttpRequest(string $method, string $url, array $headers, ?array $body = null): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($error) {
            throw new ToSendException('cURL error: ' . $error);
        }

        return $this->decodeResponse(is_string($response) ? $response : '', (int) $httpCode);
    }

    /**
     * Decode a response body, throwing on an HTTP error status.
     *
     * @throws ToSendException
     */
    protected function decodeResponse(string $response, int $httpCode): array
    {
        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            $decoded = [];
        }

        if ($httpCode >= 400) {
            throw ToSendException::fromResponse($decoded, $httpCode);
        }

        return $decoded;
    }
}
