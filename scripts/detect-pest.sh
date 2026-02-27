#!/bin/bash
# scripts/detect-pest.sh
# Enforces Rule 12-GAT-006: PHPUnit-Only/Pest Detection Gate
set -e

echo "Running Pest usage detection..."

# Check composer dependencies for pestphp/pest only (not plugin keys in config)
if php -r '
$composer = json_decode(file_get_contents("composer.json"), true);
$require = array_keys($composer["require"] ?? []);
$requireDev = array_keys($composer["require-dev"] ?? []);
$packages = array_merge($require, $requireDev);
exit(in_array("pestphp/pest", $packages, true) ? 0 : 1);
'; then
    echo "ERROR: pestphp/pest dependency found in composer.json under strictly PHPUnit-only policy."
    exit 1
fi

# Check Modules tests for Pest syntax
if [ -d "Modules" ]; then
    if rg -n '^\s*(it|test)\(' Modules/*/tests; then
        echo "ERROR: Pest syntax (it/test) found in Modules/*/tests. Only PHPUnit syntax is allowed."
        exit 1
    fi
fi

echo "Pest detection passed."
exit 0
