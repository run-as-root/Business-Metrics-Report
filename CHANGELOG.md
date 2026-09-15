# Changelog

## 1.0.0 - 2026-09-15

Initial release.

### Added

- Daily and weekly cron jobs reporting revenue and order count metrics, with independently configurable schedules
- Historical comparisons for every metric against week-ago, month-ago, and year-ago values, using store timezone date ranges
- Slack notification channel: a Block Kit summary message followed by the full detailed report as a thread reply
- Email notification channel: an HTML report sent to any number of recipients via Magento's `TransportBuilder`
- Fluctuation-only mode to skip all notification channels for a run unless a metric's week-over-week decline crosses a configurable threshold
- Fluctuation warning banner in the Slack summary message when a metric's week-over-week decline crosses the configured threshold
- Admin configuration under **Stores → Configuration → Run as Root → Business Metrics Report** for enabling reports, cron schedules, Slack credentials, email recipients, and fluctuation thresholds
- Extension points for custom metric collectors (`MetricCollectorInterface`), notification channels (`NotificationChannelInterface`), Slack report formatters (`SlackReportFormatterInterface`), email report formatters (`EmailReportFormatterInterface`), and the metrics collection strategy (`MetricsCollectionStrategyInterface`)
- Built-in revenue and order count collectors, aggregating `processing` orders with a non-zero grand total across the entire installation
