# ToSend Laravel SDK

Official Laravel SDK for the [ToSend](https://tosend.com) email API.

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x

## Installation

```bash
composer require tosend/tosend-laravel
```

## Configuration

Add your API key to your `.env` file:

```env
TOSEND_API_KEY=tsend_your_api_key
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=tosend-config
```

### Configuration Options

```php
// config/tosend.php
return [
    'api_key' => env('TOSEND_API_KEY'),
    'api_url' => env('TOSEND_API_URL', 'https://api.tosend.com'),
    'from' => [
        'address' => env('TOSEND_FROM_ADDRESS'),
        'name' => env('TOSEND_FROM_NAME'),
    ],
    'timeout' => env('TOSEND_TIMEOUT', 30),
];
```

## Usage

### Using the Facade

```php
use ToSend\Laravel\Facades\ToSend;

$response = ToSend::send([
    'from' => ['email' => 'hello@yourdomain.com', 'name' => 'Your App'],
    'to' => [['email' => 'user@example.com']],
    'subject' => 'Welcome!',
    'html' => '<h1>Hello World</h1>',
]);

echo $response->messageId;
```

### Using Dependency Injection

```php
use ToSend\Laravel\Contracts\ToSendClient;

class EmailController extends Controller
{
    public function send(ToSendClient $tosend)
    {
        $response = $tosend->send([
            'from' => ['email' => 'hello@yourdomain.com'],
            'to' => [['email' => 'user@example.com']],
            'subject' => 'Hello!',
            'html' => '<p>Welcome to our app!</p>',
        ]);

        return $response->messageId;
    }
}
```

### Using the Email Builder

```php
use ToSend\Laravel\Facades\ToSend;
use ToSend\Laravel\Data\Email;
use ToSend\Laravel\Data\Attachment;

$email = Email::make(
    from: ['email' => 'hello@yourdomain.com', 'name' => 'Your App'],
    subject: 'Your Invoice'
)
    ->to(['email' => 'user@example.com', 'name' => 'John Doe'])
    ->to('another@example.com')
    ->cc('manager@example.com')
    ->bcc(['email' => 'archive@example.com'])
    ->html('<h1>Invoice Attached</h1>')
    ->text('Invoice Attached')
    ->attach(Attachment::fromPath('/path/to/invoice.pdf'));

$response = ToSend::send($email);
```

### Batch Sending

```php
use ToSend\Laravel\Facades\ToSend;

$response = ToSend::batch([
    [
        'from' => ['email' => 'hello@yourdomain.com'],
        'to' => [['email' => 'user1@example.com']],
        'subject' => 'Hello User 1',
        'html' => '<p>Welcome!</p>',
    ],
    [
        'from' => ['email' => 'hello@yourdomain.com'],
        'to' => [['email' => 'user2@example.com']],
        'subject' => 'Hello User 2',
        'html' => '<p>Welcome!</p>',
    ],
]);

// Check results
echo "Sent: " . $response->successCount();
echo "Failed: " . $response->failedCount();

foreach ($response->results as $result) {
    if ($result->isSuccess()) {
        echo "Sent: " . $result->messageId;
    } else {
        echo "Failed: " . $result->message;
    }
}
```

### Account Information

```php
use ToSend\Laravel\Facades\ToSend;

$info = ToSend::getAccountInfo();

echo $info->title;
echo $info->emailsUsageThisMonth;
echo $info->emailsSentLast24Hours;

foreach ($info->domains as $domain) {
    echo $domain->domainName . ': ' . $domain->verificationStatus;
}

// Get only verified domains
$verified = $info->verifiedDomains();
```

## Laravel Mail Integration

Use ToSend as your Laravel mail driver:

### Configure Mail Driver

```env
MAIL_MAILER=tosend
TOSEND_API_KEY=tsend_your_api_key
TOSEND_FROM_ADDRESS=hello@yourdomain.com
TOSEND_FROM_NAME="Your App"
```

```php
// config/mail.php
'mailers' => [
    'tosend' => [
        'transport' => 'tosend',
    ],
],
```

### Send with Laravel Mail

```php
use Illuminate\Support\Facades\Mail;

// Using a Mailable
Mail::to('user@example.com')->send(new WelcomeEmail());

// Using the mail facade directly
Mail::mailer('tosend')
    ->to('user@example.com')
    ->send(new WelcomeEmail());
```

### Create a Mailable

```php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;

class WelcomeEmail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address('hello@yourdomain.com', 'Your App'),
            subject: 'Welcome to Our App',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath('/path/to/file.pdf'),
        ];
    }
}
```

## Attachments

### From File Path

```php
use ToSend\Laravel\Data\Attachment;

$attachment = Attachment::fromPath('/path/to/document.pdf');

// With custom name and type
$attachment = Attachment::fromPath(
    path: '/path/to/document.pdf',
    name: 'custom-name.pdf',
    type: 'application/pdf'
);
```

### From Content

```php
$attachment = Attachment::fromContent(
    content: $pdfContent,
    name: 'report.pdf',
    type: 'application/pdf'
);
```

### From Base64

```php
$attachment = Attachment::fromBase64(
    base64Content: $base64String,
    name: 'image.png',
    type: 'image/png'
);
```

## Error Handling

```php
use ToSend\Laravel\Facades\ToSend;
use ToSend\Laravel\Exceptions\ToSendException;

try {
    $response = ToSend::send([
        'from' => ['email' => 'hello@yourdomain.com'],
        'to' => [['email' => 'user@example.com']],
        'subject' => 'Hello',
        'html' => '<p>Hello</p>',
    ]);
} catch (ToSendException $e) {
    // Get error message
    echo $e->getMessage();

    // Get HTTP status code
    echo $e->getCode();

    // Get validation errors
    $errors = $e->getErrors();

    // Check error type
    if ($e->isValidationError()) {
        // Handle validation error (422)
    }

    if ($e->isAuthenticationError()) {
        // Handle auth error (401/403)
    }

    if ($e->isRateLimitError()) {
        // Handle rate limit (429)
    }
}
```

## Testing

For testing, you can mock the ToSend client:

```php
use ToSend\Laravel\Contracts\ToSendClient;
use ToSend\Laravel\Data\EmailResponse;

public function test_sends_welcome_email()
{
    $mock = $this->mock(ToSendClient::class);

    $mock->shouldReceive('send')
        ->once()
        ->andReturn(new EmailResponse(messageId: 'test-message-id'));

    // Your test code...
}
```

Or use a custom base URL for testing:

```env
# .env.testing
TOSEND_API_URL=http://localhost:8080
```

## License

MIT
