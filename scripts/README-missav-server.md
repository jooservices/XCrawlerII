# MissAV server runbook

## One-time setup

```bash
composer install
composer dump-autoload
php artisan migrate
vendor/bin/playwright-install --browsers
```

If running on a headless server, install Xvfb and deps:

```bash
bash scripts/setup-missav-headless.sh
```

## Run in production (examples)

- Run scheduler (includes MissAV list fetch and process command):

```bash
bash scripts/run-missav-scheduler.sh
```

- Manually enqueue list + process:

```bash
bash scripts/run-missav-new.sh
bash scripts/run-missav-process.sh
```

- Process a single item:

```bash
php artisan jav:missav:item "https://missav.ai/en/spsb-038"
```

## Notes

- MissAV HTML temp files are stored under `storage/app/tmp/missav`.
- Playwright is configured via `jav.missav.playwright.*`.
- Headless Playwright is blocked by Cloudflare; run with a virtual display.
