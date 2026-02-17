#!/usr/bin/env bash
set -euo pipefail

# Dispatch MissAV detail jobs from the schedule table.

LIMIT=${LIMIT:-5}

php artisan jav:missav:process --limit="$LIMIT"
