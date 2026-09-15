<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Email;

use RunAsRoot\BusinessMetricsReport\Api\Channel\Email\EmailFormattedReportInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Email\Sender;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SenderTest extends TestCase
{
    private TransportBuilder|MockObject $transportBuilder;
    private Config|MockObject $config;
    private StoreManagerInterface|MockObject $storeManager;
    private LoggerInterface|MockObject $logger;
    private Sender $sut;

    protected function setUp(): void
    {
        $this->transportBuilder = $this->createMock(TransportBuilder::class);
        $this->config = $this->createMock(Config::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->sut = new Sender(
            $this->transportBuilder,
            $this->config,
            $this->storeManager,
            $this->logger
        );
    }

    public function testReturnsFalseWhenNoRecipients(): void
    {
        $this->config->method('getEmailRecipients')->willReturn([]);
        $this->logger->expects($this->once())->method('error');

        $result = $this->sut->send($this->buildReport());

        $this->assertFalse($result);
    }

    public function testReturnsTrueOnSuccessfulSend(): void
    {
        $this->config->method('getEmailRecipients')->willReturn(['test@example.com']);
        $this->setupStoreManager(1);
        $this->setupTransportBuilderChain(['test@example.com']);

        $result = $this->sut->send($this->buildReport());

        $this->assertTrue($result);
    }

    public function testAddsAllRecipientsToTransport(): void
    {
        $recipients = ['a@example.com', 'b@example.com'];
        $this->config->method('getEmailRecipients')->willReturn($recipients);
        $this->setupStoreManager(1);
        $this->setupTransportBuilderChain($recipients);

        $this->sut->send($this->buildReport());
    }

    public function testReturnsFalseAndLogsOnTransportException(): void
    {
        $this->config->method('getEmailRecipients')->willReturn(['test@example.com']);
        $this->setupStoreManager(1);

        $this->transportBuilder->method('setTemplateIdentifier')
            ->willThrowException(new \RuntimeException('Transport error'));

        $this->logger->expects($this->once())->method('error');

        $result = $this->sut->send($this->buildReport());

        $this->assertFalse($result);
    }

    private function buildReport(): EmailFormattedReportInterface
    {
        $report = $this->createMock(EmailFormattedReportInterface::class);
        $report->method('getSubject')->willReturn('Subject');
        $report->method('getHeaderTitle')->willReturn('Daily Report');
        $report->method('getHeaderPeriod')->willReturn('Yesterday — Feb 18, 2026');
        $report->method('getMetrics')->willReturn([]);

        return $report;
    }

    private function setupStoreManager(int $storeId): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn($storeId);
        $this->storeManager->method('getStore')->willReturn($store);
    }

    private function setupTransportBuilderChain(array $recipients): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())->method('sendMessage');

        $this->transportBuilder->method('setTemplateIdentifier')->willReturn($this->transportBuilder);
        $this->transportBuilder->method('setTemplateOptions')->willReturn($this->transportBuilder);
        $this->transportBuilder->method('setTemplateVars')->willReturn($this->transportBuilder);
        $this->transportBuilder->method('setFromByScope')->willReturn($this->transportBuilder);
        $this->transportBuilder->expects($this->exactly(count($recipients)))
            ->method('addTo')
            ->willReturn($this->transportBuilder);
        $this->transportBuilder->method('getTransport')->willReturn($transport);
    }
}
