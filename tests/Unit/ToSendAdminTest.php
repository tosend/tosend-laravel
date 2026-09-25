<?php

namespace ToSend\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ToSend\Laravel\Exceptions\ToSendException;
use ToSend\Laravel\ToSendAdmin;

/**
 * Captures every request instead of sending it, and replies with a canned body.
 */
class RecordingAdmin extends ToSendAdmin
{
    public array $calls = [];
    public array $reply = [];

    protected function performHttpRequest(string $method, string $url, array $headers, ?array $body = null): array
    {
        $this->calls[] = compact('method', 'url', 'headers', 'body');
        return $this->reply;
    }

    public function decode(string $response, int $status): array
    {
        return $this->decodeResponse($response, $status);
    }
}

class ToSendAdminTest extends TestCase
{
    private const KEY = 'tsend_admin_0123456789abcdef0123456789abcdef';

    public function test_rejects_a_sending_key(): void
    {
        try {
            new ToSendAdmin('tsend_0a1b2c3d4e5f60718293a4b5c6d7e8f9');
            $this->fail('A sending key must be refused');
        } catch (ToSendException $e) {
            $this->assertSame(401, $e->getCode());
            $this->assertStringContainsString('This is not an admin API key', $e->getMessage());
            $this->assertStringContainsString('tsend_admin_', $e->getMessage());
        }
    }

    public function test_rejects_an_empty_key(): void
    {
        $this->expectException(ToSendException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('Admin API key is required');

        new ToSendAdmin('   ');
    }

    public function test_sends_the_key_as_a_bearer_token_and_no_account_header_by_default(): void
    {
        $admin = new RecordingAdmin(self::KEY);
        $admin->reply = ['data' => [], 'total' => 0, 'page' => 1, 'per_page' => 25];

        $admin->listDomains();

        $headers = $admin->calls[0]['headers'];
        $this->assertContains('Authorization: Bearer ' . self::KEY, $headers);
        $this->assertEmpty(preg_grep('/^X-Account-Id:/', $headers));
    }

    public function test_for_account_adds_the_header_without_changing_the_original(): void
    {
        $admin = new RecordingAdmin(self::KEY);
        $child = $admin->forAccount(4203);

        $child->listDomains();
        $admin->listDomains();

        $this->assertNotSame($admin, $child);
        $this->assertContains('X-Account-Id: 4203', $child->calls[0]['headers']);
        $this->assertEmpty(preg_grep('/^X-Account-Id:/', $admin->calls[0]['headers']));
    }

    public function test_list_sending_keys_passes_paging_and_returns_the_full_body(): void
    {
        $admin = new RecordingAdmin(self::KEY);
        $admin->reply = ['data' => [['id' => 1]], 'total' => 30, 'page' => 2, 'per_page' => 10];

        $result = $admin->listSendingKeys(['page' => 2, 'per_page' => 10]);

        $this->assertSame('GET', $admin->calls[0]['method']);
        $this->assertSame('https://api.tosend.com/v2/api-keys?page=2&per_page=10', $admin->calls[0]['url']);
        $this->assertNull($admin->calls[0]['body']);
        $this->assertSame(30, $result['total']);
        $this->assertSame(2, $result['page']);
    }

    public function test_delete_suppression_encodes_the_email_in_the_query(): void
    {
        $admin = new RecordingAdmin(self::KEY);
        $admin->reply = ['deleted' => true, 'remaining_today' => 499];

        $result = $admin->deleteSuppression('a+b@example.com');

        $this->assertSame('DELETE', $admin->calls[0]['method']);
        $this->assertSame('https://api.tosend.com/v2/suppressions?email=a%2Bb%40example.com', $admin->calls[0]['url']);
        $this->assertSame(499, $result['remaining_today']);
    }

    public function test_path_ids_are_url_encoded(): void
    {
        $admin = new RecordingAdmin(self::KEY);

        $admin->getDomain('abc/../x');

        $this->assertSame('https://api.tosend.com/v2/domains/abc%2F..%2Fx', $admin->calls[0]['url']);
    }

    public function test_create_sending_key_unwraps_data_and_omits_a_null_domain(): void
    {
        $admin = new RecordingAdmin(self::KEY);
        $admin->reply = ['data' => ['id' => 9, 'api_key' => 'tsend_x']];

        $key = $admin->createSendingKey('Client');

        $this->assertSame(['name' => 'Client'], $admin->calls[0]['body']);
        $this->assertSame('tsend_x', $key['api_key']);
    }

    public function test_update_team_uses_put(): void
    {
        $admin = new RecordingAdmin(self::KEY);
        $admin->reply = ['data' => ['id' => 7, 'name' => 'Acme', 'status' => 'suspended']];

        $team = $admin->updateTeam(7, ['status' => 'suspended']);

        $this->assertSame('PUT', $admin->calls[0]['method']);
        $this->assertSame('https://api.tosend.com/v2/teams/7', $admin->calls[0]['url']);
        $this->assertSame('suspended', $team['status']);
    }

    public function test_send_posts_to_the_sending_endpoint(): void
    {
        $admin = new RecordingAdmin(self::KEY);
        $admin->reply = ['message_id' => 'msg_1'];

        $response = $admin->forAccount(4203)->send(['subject' => 'Hi']);

        $this->assertSame('msg_1', $response->messageId);
    }

    public function test_decodes_a_flat_admin_error(): void
    {
        $admin = new RecordingAdmin(self::KEY);

        try {
            $admin->decode(json_encode([
                'status_code' => 403,
                'message' => 'This admin key does not have the domains:read scope.',
                'errors' => ['code' => 'insufficient_scope', 'required_scope' => 'domains:read'],
            ]), 403);
            $this->fail('A 403 must throw');
        } catch (ToSendException $e) {
            $this->assertSame(403, $e->getCode());
            $this->assertSame('insufficient_scope', $e->getErrorCode());
            $this->assertSame('domains:read', $e->getErrors()['required_scope']);
            // No error_type on Admin API errors: derived from the status.
            $this->assertSame(ToSendException::TYPE_FORBIDDEN, $e->getErrorType());
        }
    }

    public function test_decodes_a_nested_sending_error(): void
    {
        $admin = new RecordingAdmin(self::KEY);

        try {
            $admin->decode(json_encode([
                'status_code' => 422,
                'error_type' => 'validation_error',
                'message' => 'Subject is required.',
                'errors' => ['subject' => ['required' => 'Subject is required.']],
            ]), 422);
            $this->fail('A 422 must throw');
        } catch (ToSendException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertSame('validation_error', $e->getErrorType());
            $this->assertNull($e->getErrorCode());
            $this->assertSame('Subject is required.', $e->getErrors()['subject']['required']);
        }
    }

    public function test_a_non_json_error_body_keeps_the_status(): void
    {
        $admin = new RecordingAdmin(self::KEY);

        try {
            $admin->decode('<html>Bad gateway</html>', 502);
            $this->fail('A 502 must throw');
        } catch (ToSendException $e) {
            $this->assertSame(502, $e->getCode());
            $this->assertSame('Unknown error', $e->getMessage());
        }
    }
}
