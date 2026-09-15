<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Slack;

use RunAsRoot\BusinessMetricsReport\Exception\SlackApiException;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

class Client
{
    private const API_URL = 'https://slack.com/api/chat.postMessage';

    public function __construct(
        private readonly Curl $curl,
        private readonly Json $json,
        private readonly Config $config
    ) {
    }

    /**
     * Posts a message to Slack and returns the message timestamp on success.
     *
     * @param array<string, mixed> $payload
     * @throws SlackApiException
     */
    public function post(array $payload): string
    {
        $botToken = $this->config->getBotToken();

        if (!$botToken) {
            throw new SlackApiException('Slack bot token is not configured');
        }

        try {
            $jsonPayload = (string) $this->json->serialize($payload);
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Authorization', 'Bearer ' . $botToken);
            $this->curl->post(self::API_URL, $jsonPayload);
        } catch (\Throwable $e) {
            throw new SlackApiException('HTTP request to Slack failed: ' . $e->getMessage(), 0, $e);
        }

        $status = $this->curl->getStatus();
        $body = $this->curl->getBody();

        if ($status !== 200) {
            throw new SlackApiException(sprintf('Slack API returned unexpected HTTP status %d', $status));
        }

        try {
            $response = $this->json->unserialize($body);
        } catch (\Throwable $e) {
            throw new SlackApiException('Failed to parse Slack API response: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($response)) {
            throw new SlackApiException('Unexpected Slack API response format');
        }

        if (empty($response['ok'])) {
            throw new SlackApiException(sprintf(
                'Slack API returned an error: %s',
                $response['error'] ?? 'unknown'
            ));
        }

        $ts = $response['ts'] ?? null;

        if ($ts === null) {
            throw new SlackApiException('Slack API response missing message timestamp');
        }

        return (string) $ts;
    }
}
