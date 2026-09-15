<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Slack;

class SummaryMessageBuilder
{
    /**
     * @param array<string, string> $titles
     */
    public function __construct(
        private readonly array $titles = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $reportType, bool $hasFluctuation): array
    {
        $blocks = [];

        $blocks[] = [
            'type' => 'header',
            'text' => [
                'type' => 'plain_text',
                'text' => $this->titles[$reportType] ?? '📊 Business Metrics Report',
            ],
        ];

        if ($hasFluctuation) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '⚠️ Fluctuation, please check details in the report',
                ],
            ];
        }

        return ['blocks' => $blocks];
    }
}
