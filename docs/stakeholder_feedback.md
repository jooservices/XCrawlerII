# Stakeholder Feedback & Requirements

## 1. 👤 End User
*Focus: Simplicity, Reliability, and "Set it and Forget it"*

*   **Do you need any adding features?**
    *   **Simple Scheduler:** I want to be able to say "Run this every Monday at 9 AM" directly from the UI without needing to know cron syntax.
    *   **Export to Excel/CSV:** I see JSON options, but I really just need an Excel file I can open immediately.
    *   **Clone Job:** I often need to run a similar crawl with just one parameter changed. A "Duplicate" button would save me time.
    *   **Pause/Resume:** If I see something wrong, I want to pause the crawler, fix the config, and resume without starting over.
*   **Do you need any more reporting?**
    *   **Summary Email:** Just send me a simple email when the job is done: "100 pages crawled, 0 errors."
*   **Do you need any UI/UX improve?**
    *   **Wizard Mode:** The current configuration has too many options. I’d like a "Simple Mode" where I just paste a URL and click "Go".
    *   **Mobile View:** I sometimes check status on my phone; the dashboard charts (ApexCharts) need to be responsive and not squished.
*   **Do you want to receive anything like notif etc?**
    *   Yes, browser push notifications when a long-running job finishes would be great so I can switch tabs.

## 2. 🤓 Advanced End User
*Focus: Configuration, Edge Cases, and Data Control*

*   **Do you need any adding features?**
    *   **Proxy Rotation Manager:** I need to upload a list of proxies and have the crawler rotate them automatically to avoid 403s.
    *   **Custom Headers/Cookies:** I need a UI to inject specific session cookies for crawling behind logins.
    *   **Rate Limiting Strategies:** I need to define "Max 2 requests per second per domain" to be polite and avoid bans.
    *   **Chained Requests:** Support for "List -> Detail" patterns where the crawler extracts links from a listing page and visits each one to get details.
    *   **CAPTCHA Solving:** Integration with 2Captcha or similar services for when I hit a wall.
*   **Do you need any CLI feature?**
    *   **Config Validation:** A command like `xcrawler validate config.json` to check if my complex rules are valid before running a 10-hour job.
    *   **Interactive Shell:** A REPL mode where I can test selectors against a URL live in the terminal.
*   **Do you need any more reporting?**
    *   **Error Breakdown:** Don't just tell me "5 errors". I need a report grouping errors by type (e.g., "5x 404 Not Found", "2x Connection Timeout") so I can fix the source.
*   **Any extra thing?**
    *   **Dry Run:** A feature to crawl just the first 5 pages and stop, so I can verify my selectors work before consuming all my quota/resources.

## 3. 👨‍💻 Developer
*Focus: SDKs (JOOClient), Events, and Integration*

*   **Do you need any adding features?**
    *   **Middleware Injection:** In `JOOClient`, I'd like easier access to inject custom Guzzle middleware without rebuilding the whole client stack manually.
    *   **Mocking Utilities:** Since you use Mockery, please expose a `TestingHelper` trait in the package so I can easily mock the `JOOClient` responses in my own PHPUnit tests without writing verbose Guzzle MockHandler code every time.
    *   **Plugin Architecture:** Allow me to write custom "Processors" in PHP that implement a specific interface to manipulate data before saving.
    *   **OpenAPI/Swagger Spec:** I want to build a custom mobile app on top of your API; I need a generated Swagger file.
*   **Do you need any extra events?**
    *   **Granular Lifecycle Events:** I need events for `RequestRetrying` (triggered by your RetryMiddleware) and `CircuitBreakerOpen`. Right now, it happens silently. I want to log these specifically to my monitoring tools.
    *   **RxJS Streams:** On the frontend/Node side, expose the crawl progress as an RxJS `Observable` so I can `subscribe()` to it for real-time UI updates instead of polling.
    *   **JobQueue Events:** `JobQueued`, `JobProcessing`, `JobProcessed`. Useful for building a custom progress bar.
*   **Do you need any CLI feature?**
    *   **Code Generation:** A command like `php artisan jooclient:make-request GetUserRequest` to scaffold request classes.
*   **Any extra thing?**
    *   **TypeScript Types:** Ensure the API responses have full TypeScript definitions (like `swiper-options.d.ts`) so I get autocomplete in my IDE when consuming the crawler results.

## 4. 🚀 DevOps / SRE
*Focus: Stability, Logging, and Automation*

*   **Do you need any CLI feature?**
    *   **Health Check:** A `health:check` command that returns a non-zero exit code if the service can't connect to the database or external APIs. Essential for Kubernetes liveness probes.
    *   **Prune Command:** A command to clean up old logs or cached responses (PSR-16) that are older than X days.
    *   **Queue Management:** Commands to `queue:flush` or `queue:retry-failed` without needing direct DB access.
    *   **System Info:** A command to dump current memory usage, CPU load, and active threads for debugging.
*   **Do you need any more reporting?**
    *   **Structured Logging:** Ensure all logs (Monolog) are output in JSON format with correlation IDs so I can trace a request from the CLI through to the `JOOClient` calls in ELK/Datadog.
    *   **Prometheus Endpoint:** Expose a `/metrics` endpoint for scraping by Prometheus (counters for requests, errors, bytes downloaded).
*   **Do you want to receive anything like notif etc?**
    *   **Webhook Alerts:** Instead of email, let me configure a generic Webhook URL so I can pipe critical failures (like Circuit Breaker opening) directly to Slack or PagerDuty.
*   **Any extra thing?**
    *   **Docker/Env Config:** Ensure all timeouts, retry limits, and memory limits are configurable via `.env` variables so I don't have to touch PHP code to tune performance in production.

## Additional Feedback: Job Failure Details and Retries

Based on recent CLI report usage, the following suggestions are proposed to improve job monitoring and reliability:

- **Expose Failure Details:**
  - Enhance job logging to capture and display detailed error messages, exception traces, and input data for each failed job.
  - Provide CLI options to view failure reasons for individual jobs.

- **Retry Mechanisms:**
  - Implement automatic retries for transient or recoverable errors (e.g., network issues, timeouts).
  - Add a manual retry command in the CLI to allow users to re-run failed jobs on demand.
  - Track retry counts and prevent infinite retry loops.

- **Alerting and Analysis:**
  - Set up notifications or alerts for jobs with high failure rates.
  - Regularly analyze root causes for jobs with persistent failures and address them proactively.

These improvements will help users understand why jobs fail, recover from transient issues, and maintain higher system reliability.
## 5. 📊 Business Analyst (BA)
*Focus: Trends, Data Quality, and ROI*

*   **Do you need any more reporting?**
    *   **Success Rate Trends:** Use those ApexCharts to show me a line graph of "Pages Crawled vs. Failed" over the last 30 days. I need to know if our data sources are becoming less reliable.
    *   **Performance Metrics:** A chart showing average response times. If the target site is slowing down, it impacts our data freshness.
    *   **Cost Analysis:** If we use paid proxies, calculate the "Cost per Record" so I can calculate ROI.
    *   **Anomaly Detection:** Alert me if the number of results deviates significantly from the average (e.g., "Usually 1000 items, today 5").
*   **Do you need any UI improve?**
    *   **Dashboard Widgets:** I want a high-level "Executive Dashboard" that just shows big numbers: "Total Records Extracted Today", "Data Freshness Score".
*   **Any extra thing?**
    *   **Data Comparison:** A feature to compare the results of today's crawl with yesterday's to highlight what changed (Diff view).
    *   **Export to BI Tools:** Direct connector to Tableau or PowerBI (or just an OData feed).
