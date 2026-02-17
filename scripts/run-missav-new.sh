#!/usr/bin/env bash
set -euo pipefail

# Fetch MissAV new list and enqueue schedule rows.

php artisan jav:sync:content missav --type=new
