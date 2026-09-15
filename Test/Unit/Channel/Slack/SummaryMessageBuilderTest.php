<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Slack;

use RunAsRoot\BusinessMetricsReport\Channel\Slack\SummaryMessageBuilder;
use PHPUnit\Framework\TestCase;

class SummaryMessageBuilderTest extends TestCase
{
    private function makeSut(): SummaryMessageBuilder
    {
        return new SummaryMessageBuilder([
            'daily' => '📊 Daily Business Metrics Report',
            'weekly' => '📊 Weekly Business Metrics Report',
        ]);
    }

    public function testBuildsDailyMessageWithoutFluctuation(): void
    {
        $payload = $this->makeSut()->build('daily', false);

        $this->assertCount(1, $payload['blocks']);
        $this->assertSame('header', $payload['blocks'][0]['type']);
        $this->assertSame('📊 Daily Business Metrics Report', $payload['blocks'][0]['text']['text']);
    }

    public function testBuildsWeeklyMessageWithoutFluctuation(): void
    {
        $payload = $this->makeSut()->build('weekly', false);

        $this->assertCount(1, $payload['blocks']);
        $this->assertSame('header', $payload['blocks'][0]['type']);
        $this->assertSame('📊 Weekly Business Metrics Report', $payload['blocks'][0]['text']['text']);
    }

    public function testAddsFluctuationWarningWhenFluctuationDetected(): void
    {
        $payload = $this->makeSut()->build('daily', true);

        $this->assertCount(2, $payload['blocks']);
        $this->assertSame('section', $payload['blocks'][1]['type']);
        $this->assertStringContainsString('⚠️', $payload['blocks'][1]['text']['text']);
        $this->assertStringContainsString('Fluctuation', $payload['blocks'][1]['text']['text']);
    }

    public function testFallbackTitleForUnknownReportType(): void
    {
        $payload = $this->makeSut()->build('unknown', false);

        $this->assertSame('📊 Business Metrics Report', $payload['blocks'][0]['text']['text']);
    }

    public function testCustomInjectedTitleIsUsed(): void
    {
        $sut = new SummaryMessageBuilder(['monthly' => '📊 Monthly Business Metrics Report']);

        $payload = $sut->build('monthly', false);

        $this->assertSame('📊 Monthly Business Metrics Report', $payload['blocks'][0]['text']['text']);
    }

    public function testEmptyTitlesArrayAlwaysUsesFallback(): void
    {
        $sut = new SummaryMessageBuilder([]);

        $daily = $sut->build('daily', false);
        $weekly = $sut->build('weekly', false);

        $this->assertSame('📊 Business Metrics Report', $daily['blocks'][0]['text']['text']);
        $this->assertSame('📊 Business Metrics Report', $weekly['blocks'][0]['text']['text']);
    }

    public function testPayloadHasBlocksKey(): void
    {
        $payload = $this->makeSut()->build('daily', false);

        $this->assertArrayHasKey('blocks', $payload);
    }

    public function testHeaderBlockHasPlainTextType(): void
    {
        $payload = $this->makeSut()->build('daily', false);

        $this->assertSame('plain_text', $payload['blocks'][0]['text']['type']);
    }
}
