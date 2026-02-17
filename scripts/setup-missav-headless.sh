#!/usr/bin/env bash
set -euo pipefail

# Install a virtual display and Playwright dependencies on Debian/Ubuntu.
# Adjust package manager for other distros.
if command -v apt-get >/dev/null 2>&1; then
  sudo apt-get update
  sudo apt-get install -y xvfb libnss3 libatk-bridge2.0-0 libatk1.0-0 libcups2 libdrm2 libgbm1 libasound2
fi

# Install Playwright browsers for PHP.
if [ -f "vendor/bin/playwright-install" ]; then
  vendor/bin/playwright-install --browsers
fi

echo "MissAV headless setup completed."
