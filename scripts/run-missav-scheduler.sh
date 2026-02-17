#!/usr/bin/env bash
set -euo pipefail

# Run Laravel scheduler under Xvfb for MissAV Playwright jobs.

xvfb-run -a php artisan schedule:work
