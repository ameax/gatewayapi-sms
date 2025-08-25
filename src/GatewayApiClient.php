<?php

declare(strict_types=1);

namespace Ameax\GatewayApiSms;

use Ameax\GatewayApiSms\Exceptions\GatewayApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GatewayApiClient
{
    private Client $httpClient;

    private string $apiToken;

    private string $baseUrl = 'https://gatewayapi.com/rest/';

    public function __construct(string $apiToken, ?Client $httpClient = null)
    {
        $this->apiToken = $apiToken;
        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30.0,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Send SMS to recipients.
     *
     * @param array<int|string> $recipients
     * @param array<string, mixed> $options
     * @throws GatewayApiException
     * @return array{ids: array<int, string>, usage: array{total_cost: float, currency: string, countries: array<string, mixed>}}
     */
    public function sendSms(string $sender, string $message, array $recipients, array $options = []): array
    {
        if (empty($recipients)) {
            throw new GatewayApiException('Recipients list cannot be empty');
        }

        if (strlen($sender) > 15) {
            throw new GatewayApiException('Sender name cannot exceed 15 characters');
        }

        if (empty($message)) {
            throw new GatewayApiException('Message cannot be empty');
        }

        $payload = array_merge([
            'sender' => $sender,
            'message' => $message,
            'recipients' => $this->formatRecipients($recipients),
        ], $options);

        try {
            $response = $this->httpClient->post('mtsms', [
                'auth' => [$this->apiToken, ''],
                'json' => $payload,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
                throw new GatewayApiException('Invalid JSON response from API');
            }

            /** @var array{ids: array<int, string>, usage: array{total_cost: float, currency: string, countries: array<string, mixed>}} $result */
            return $result;
        } catch (GuzzleException $e) {
            throw new GatewayApiException(
                'Failed to send SMS: ' . $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }
    }

    /**
     * Format recipients for the API.
     *
     * @param array<int|string> $recipients
     * @return array<array{msisdn: string}>
     */
    private function formatRecipients(array $recipients): array
    {
        $formatted = [];

        foreach ($recipients as $recipient) {
            // Remove any non-numeric characters
            $msisdn = preg_replace('/[^0-9]/', '', (string) $recipient);

            if (empty($msisdn)) {
                throw new GatewayApiException("Invalid recipient number: {$recipient}");
            }

            $formatted[] = ['msisdn' => $msisdn];
        }

        return $formatted;
    }

    /**
     * Get the status of a sent message.
     *
     * @param string|array<string> $messageIds
     * @throws GatewayApiException
     * @return array<string, mixed>
     */
    public function getMessageStatus(string|array $messageIds): array
    {
        $ids = is_array($messageIds) ? $messageIds : [$messageIds];

        try {
            $response = $this->httpClient->get('mtsms', [
                'auth' => [$this->apiToken, ''],
                'query' => [
                    'ids' => implode(',', $ids),
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
                throw new GatewayApiException('Invalid JSON response from API');
            }

            /** @var array<string, mixed> $result */
            return $result;
        } catch (GuzzleException $e) {
            throw new GatewayApiException(
                'Failed to get message status: ' . $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }
    }

    /**
     * Cancel scheduled messages.
     *
     * @param string|array<string> $messageIds
     * @throws GatewayApiException
     * @return array<string, mixed>
     */
    public function cancelMessages(string|array $messageIds): array
    {
        $ids = is_array($messageIds) ? $messageIds : [$messageIds];

        try {
            $response = $this->httpClient->delete('mtsms', [
                'auth' => [$this->apiToken, ''],
                'query' => [
                    'ids' => implode(',', $ids),
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
                throw new GatewayApiException('Invalid JSON response from API');
            }

            /** @var array<string, mixed> $result */
            return $result;
        } catch (GuzzleException $e) {
            throw new GatewayApiException(
                'Failed to cancel messages: ' . $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }
    }
}
