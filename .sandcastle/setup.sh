#!/usr/bin/env bash
# Runs in each implementer, reviewer and merger sandbox before the agent starts.
set -euo pipefail

composer install --no-interaction --no-progress --prefer-dist
