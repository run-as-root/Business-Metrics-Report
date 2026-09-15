<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Email;

use RunAsRoot\BusinessMetricsReport\Api\Channel\Email\EmailFormattedReportInterface;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Sender
{
    private const TEMPLATE_ID = 'run_as_root_business_metrics_report';
    private const SENDER_IDENTITY = 'general';

    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws NoSuchEntityException
     */
    public function send(EmailFormattedReportInterface $report): bool
    {
        $recipients = $this->config->getEmailRecipients();

        if (!$recipients) {
            $this->logger->error('No email recipients configured for business metrics report');
            return false;
        }

        $storeId = (int) $this->storeManager->getStore()->getId();

        try {
            $builder = $this->transportBuilder
                ->setTemplateIdentifier(self::TEMPLATE_ID)
                ->setTemplateOptions(['area' => Area::AREA_ADMINHTML, 'store' => $storeId])
                ->setTemplateVars([
                    'subject' => $report->getSubject(),
                    'headerTitle' => $report->getHeaderTitle(),
                    'headerPeriod' => $report->getHeaderPeriod(),
                    'metrics' => $report->getMetrics(),
                ])
                ->setFromByScope(self::SENDER_IDENTITY, $storeId);

            foreach ($recipients as $recipient) {
                $builder->addTo($recipient);
            }

            $builder->getTransport()->sendMessage();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send business metrics email', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }

        return true;
    }
}
