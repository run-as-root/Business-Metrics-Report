<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\System\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_DAILY_ENABLED = 'run_as_root_business_metrics_report/general/daily_enabled';
    private const XML_PATH_WEEKLY_ENABLED = 'run_as_root_business_metrics_report/general/weekly_enabled';
    private const XML_PATH_SLACK_ENABLED = 'run_as_root_business_metrics_report/slack/enabled';
    private const XML_PATH_BOT_TOKEN = 'run_as_root_business_metrics_report/slack/bot_token';
    private const XML_PATH_CHANNEL_ID = 'run_as_root_business_metrics_report/slack/channel_id';
    private const XML_PATH_EMAIL_ENABLED = 'run_as_root_business_metrics_report/email/enabled';
    private const XML_PATH_EMAIL_RECIPIENTS = 'run_as_root_business_metrics_report/email/recipients';
    private const XML_PATH_FLUCTUATION_DECLINE_THRESHOLD =
        'run_as_root_business_metrics_report/general/fluctuation_decline_threshold';
    private const XML_PATH_ONLY_ON_FLUCTUATION =
        'run_as_root_business_metrics_report/general/only_on_fluctuation';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isDailyEnabled(?int $scopeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DAILY_ENABLED, ScopeInterface::SCOPE_STORE, $scopeId);
    }

    public function isWeeklyEnabled(?int $scopeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_WEEKLY_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    public function isSlackEnabled(?int $scopeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SLACK_ENABLED, ScopeInterface::SCOPE_STORE, $scopeId);
    }

    public function getBotToken(?int $scopeId = null): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_BOT_TOKEN,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        return $value ? (string) $value : null;
    }

    public function getChannelId(?int $scopeId = null): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CHANNEL_ID,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        return $value ? (string) $value : null;
    }

    public function isEmailEnabled(?int $scopeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_EMAIL_ENABLED, ScopeInterface::SCOPE_STORE, $scopeId);
    }

    public function getFluctuationDeclineThreshold(?int $scopeId = null): float
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_FLUCTUATION_DECLINE_THRESHOLD,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    public function isOnlyOnFluctuationEnabled(?int $scopeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ONLY_ON_FLUCTUATION,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * @return array<int, string>
     */
    public function getEmailRecipients(?int $scopeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_RECIPIENTS,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        if (!$value) {
            return [];
        }

        $result = [];
        foreach (explode(',', (string)$value) as $recipient) {
            $trimmed = trim($recipient);
            if ($trimmed !== '') {
                $result[] = $trimmed;
            }
        }

        return $result;
    }
}
