#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel)"
cd "$ROOT_DIR"

if [[ ! -d ".githooks" ]]; then
    echo "[hooks] .githooks directory not found."
    exit 1
fi

chmod +x .githooks/pre-commit .githooks/pre-push
git config core.hooksPath .githooks

echo "[hooks] Installed successfully."
echo "[hooks] pre-commit => composer lint"
echo "[hooks] pre-push   => composer test"
