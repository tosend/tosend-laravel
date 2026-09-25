<?php

namespace ToSend\Laravel;

use ToSend\Laravel\Concerns\MakesHttpRequests;
use ToSend\Laravel\Data\BatchResponse;
use ToSend\Laravel\Data\Email;
use ToSend\Laravel\Data\EmailResponse;
use ToSend\Laravel\Exceptions\ToSendException;

/**
 * Admin API client: account-level management of sending domains, delivery
 * logs, suppressions, webhooks, sending keys and client teams.
 *
 * Deliberately a separate class from ToSend: admin keys and sending keys are
 * different credentials and are not interchangeable. A sending key cannot
 * manage the account, and an admin key can only send when it holds the
 * `emails:send` scope. Every call needs the matching scope on the key; a
 * missing one throws a 403 whose getErrorCode() is `insufficient_scope`.
 *
 * Example:
 *
 *     $admin = app(\ToSend\Laravel\ToSendAdmin::class);
 *     $client = $admin->forAccount(4203);
 *
 *     $domain = $client->createDomain('client.example.com');
 *     $detail = $client->getDomain($domain['id']);
 *     // No verify call needed: toSend checks DNS every 10 minutes.
 */
class ToSendAdmin
{
    use MakesHttpRequests;

    /** Admin keys carry their own prefix so they cannot be confused with sending keys. */
    public const KEY_PREFIX = 'tsend_admin_';

    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;
    protected ?int $accountId = null;

    /**
     * @param string $apiKey Your admin API key (tsend_admin_...)
     * @throws ToSendException
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.tosend.com',
        int $timeout = 30
    ) {
        $this->apiKey = trim($apiKey);
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        if ($this->apiKey === '') {
            throw new ToSendException('Admin API key is required', 401);
        }

        // Caught here rather than as a 401 from the server: pasting a sending
        // key is the likeliest mistake, and the message should say so.
        if (!str_starts_with($this->apiKey, self::KEY_PREFIX)) {
            throw new ToSendException(
                'This is not an admin API key. Admin keys start with "' . self::KEY_PREFIX
                . '" and are created in the dashboard under API Keys.',
                401
            );
        }
    }

    /**
     * Return a client scoped to one of your child teams.
     *
     * Requests then carry X-Account-Id, so they act on that team rather than
     * on your own account. The original client is left unchanged.
     */
    public function forAccount(int $accountId): static
    {
        $clone = clone $this;
        $clone->accountId = $accountId;
        return $clone;
    }

    // -------------------------------------------------------------------------
    // Account
    // -------------------------------------------------------------------------

    /**
     * The team this key acts on, its pooled credits, and the key itself
     * (including its scopes). Needs no scope; the domain list is included
     * only when the key has `domains:read`.
     */
    public function getInfo(): array
    {
        return $this->request('GET', '/v2/info');
    }

    // -------------------------------------------------------------------------
    // Sending (requires the emails:send scope)
    // -------------------------------------------------------------------------

    /**
     * Send an email as the targeted team (the key's own, or one set with
     * forAccount()). Same body as the sending API.
     */
    public function send(array|Email $email): EmailResponse
    {
        $data = $email instanceof Email ? $email->toArray() : $email;

        return EmailResponse::fromArray($this->request('POST', '/v2/emails', $data));
    }

    /**
     * Send up to 100 emails in one request; same scope and targeting as send().
     *
     * @param array<int, array|Email> $emails
     */
    public function batch(array $emails): BatchResponse
    {
        $data = array_map(
            fn($email) => $email instanceof Email ? $email->toArray() : $email,
            array_values($emails)
        );

        return BatchResponse::fromArray($this->request('POST', '/v2/emails/batch', ['emails' => $data]));
    }

    // -------------------------------------------------------------------------
    // Domains
    // -------------------------------------------------------------------------

    /**
     * @param  array $params page, per_page (1-100, default 25)
     * @return array {data, total, page, per_page, account_id}
     */
    public function listDomains(array $params = []): array
    {
        return $this->request('GET', '/v2/domains', [], $params);
    }

    /**
     * Add a sending domain. It starts in `draft`; toSend checks DNS every 10
     * minutes and verifies it once the records resolve.
     *
     * A 409 means the domain already exists (names are unique across toSend),
     * which after a timed-out retry is usually the domain your first call
     * created. Treat it as "go read it", not as a failure.
     */
    public function createDomain(string $name): array
    {
        return $this->data($this->request('POST', '/v2/domains', ['name' => $name]));
    }

    /**
     * Domain detail plus the DNS records to publish. The DKIM key is unique to
     * the owning team, so never reuse a value for another domain or team.
     */
    public function getDomain(string $id): array
    {
        return $this->data($this->request('GET', '/v2/domains/' . rawurlencode($id)));
    }

    /**
     * Remove a domain. Does not touch DNS: remove the records yourself.
     */
    public function deleteDomain(string $id): array
    {
        return $this->request('DELETE', '/v2/domains/' . rawurlencode($id));
    }

    // -------------------------------------------------------------------------
    // Email logs
    // -------------------------------------------------------------------------

    /**
     * @param array $params page, per_page, status, domain, from, to, search
     */
    public function listEmails(array $params = []): array
    {
        return $this->request('GET', '/v2/emails', [], $params);
    }

    /** Look up one message by the message_id POST /v2/emails returned. */
    public function getEmail(string $messageId): array
    {
        return $this->data($this->request('GET', '/v2/emails/' . rawurlencode($messageId)));
    }

    // -------------------------------------------------------------------------
    // Suppressions
    // -------------------------------------------------------------------------

    /**
     * @param array $params page, per_page, search, status
     */
    public function listSuppressions(array $params = []): array
    {
        return $this->request('GET', '/v2/suppressions', [], $params);
    }

    /**
     * Reactivate an address. Limited to 500 per day per account, shared with
     * the dashboard.
     *
     * @return array {deleted, message, used_today, remaining_today, daily_limit}
     */
    public function deleteSuppression(string $email): array
    {
        return $this->request('DELETE', '/v2/suppressions', [], ['email' => $email]);
    }

    // -------------------------------------------------------------------------
    // Webhooks
    // -------------------------------------------------------------------------

    /**
     * @param  array $params page, per_page
     * @return array {data, total, page, per_page}
     */
    public function listWebhooks(array $params = []): array
    {
        return $this->request('GET', '/v2/webhooks', [], $params);
    }

    /**
     * @param array $params url and events (required: 'bounced' and/or
     *                      'complaint'), title, include_message
     * @return array The webhook, including its signing `secret`. The API
     *               returns the secret only here, so store it now.
     */
    public function createWebhook(array $params): array
    {
        return $this->data($this->request('POST', '/v2/webhooks', $params));
    }

    /**
     * @param array $params any of title, url, events, include_message, and
     *                      status ('active' | 'inactive')
     */
    public function updateWebhook(int $id, array $params): array
    {
        return $this->data($this->request('PUT', '/v2/webhooks/' . $id, $params));
    }

    public function deleteWebhook(int $id): array
    {
        return $this->request('DELETE', '/v2/webhooks/' . $id);
    }

    // -------------------------------------------------------------------------
    // Sending API keys
    // -------------------------------------------------------------------------

    /**
     * List sending keys, masked.
     *
     * @param  array $params page, per_page
     * @return array {data, total, page, per_page}
     */
    public function listSendingKeys(array $params = []): array
    {
        return $this->request('GET', '/v2/api-keys', [], $params);
    }

    /**
     * Mint a sending key for this team. The raw key is in the response and
     * nowhere else, ever. Pass $domain (a domain id) to restrict it to one
     * domain. Needs the `api_keys:write` and `emails:send` scopes.
     */
    public function createSendingKey(string $name, ?string $domain = null): array
    {
        $payload = ['name' => $name];
        if ($domain !== null) {
            $payload['domain'] = $domain;
        }

        return $this->data($this->request('POST', '/v2/api-keys', $payload));
    }

    public function deleteSendingKey(int $id): array
    {
        return $this->request('DELETE', '/v2/api-keys/' . $id);
    }

    // -------------------------------------------------------------------------
    // Teams
    // -------------------------------------------------------------------------

    /**
     * Your account and its child teams, with names. `credit_balance` is the
     * pooled figure; child teams have no balance of their own.
     */
    public function listTeams(): array
    {
        return $this->data($this->request('GET', '/v2/teams'));
    }

    /** Only the parent account can create teams, so do not use forAccount(). */
    public function createTeam(string $name): array
    {
        return $this->data($this->request('POST', '/v2/teams', ['name' => $name]));
    }

    /**
     * @param array $params name and/or status ('active' | 'suspended')
     */
    public function updateTeam(int $id, array $params): array
    {
        return $this->data($this->request('PUT', '/v2/teams/' . $id, $params));
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * @throws ToSendException
     */
    protected function request(string $method, string $endpoint, array $data = [], array $query = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $query = array_filter($query, static fn($value) => $value !== null && $value !== '');
        if ($query) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($this->accountId !== null) {
            $headers[] = 'X-Account-Id: ' . $this->accountId;
        }

        return $this->performHttpRequest($method, $url, $headers, $data ? $data : null);
    }

    protected function data(array $response): array
    {
        return is_array($response['data'] ?? null) ? $response['data'] : [];
    }
}
