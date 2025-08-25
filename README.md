# GatewayAPI SMS Package

A framework-agnostic PHP package for sending SMS via GatewayAPI.

## Requirements

- PHP 8.3 or higher
- Guzzle HTTP Client 7.5 or higher

## Installation

```bash
composer require ameax/gatewayapi-sms
```

## Usage

### Basic Usage

```php
use Ameax\GatewayApiSms\GatewayApiClient;

// Initialize the client
$client = new GatewayApiClient('your-api-token-here');

// Send SMS
$result = $client->sendSms(
    'YourSender',           // Sender name (max 15 characters)
    'Hello, this is a test message!', // Message content
    [4512345678, 4587654321] // Recipients
);

// The result contains message IDs and usage information
print_r($result['ids']);
```

### Advanced Usage

```php
// Send with additional options
$result = $client->sendSms(
    'YourSender',
    'Hello, this is a test message!',
    [4512345678, 4587654321],
    [
        'sendtime' => time() + 3600, // Schedule for 1 hour from now
        'class' => 'premium',         // Message class
        'priority' => 'high',         // Priority
    ]
);

// Get message status
$status = $client->getMessageStatus($result['ids']);

// Cancel scheduled messages
$cancelResult = $client->cancelMessages($result['ids']);
```

### Exception Handling

```php
use Ameax\GatewayApiSms\GatewayApiClient;
use Ameax\GatewayApiSms\Exceptions\GatewayApiException;

try {
    $client = new GatewayApiClient('your-api-token');
    $result = $client->sendSms('Sender', 'Message', [4512345678]);
} catch (GatewayApiException $e) {
    echo 'Error: ' . $e->getMessage();
}
```

## Testing

```bash
composer test
```

## License

MIT