#!/usr/bin/env bash
# Quick check that a deployed site is alive:  scripts/smoke-test.sh https://ahla.example.com
set -euo pipefail
BASE="${1:?usage: smoke-test.sh <base-url>}"
fail=0
check() {
  local path="$1" expect="$2" code
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$BASE$path")
  if [ "$code" = "$expect" ]; then echo "ok   $path -> $code"; else echo "FAIL $path -> $code (expected $expect)"; fail=1; fi
}
check /up 200
check / 200
check /search 200
check /login 200
check /api/search 200
check /api/health 200
check /admin/verifications 302   # guests are sent to login
check /.env 403                  # secrets are never served (nginx denies dotfiles)
curl -sI --max-time 15 "$BASE/" | grep -qi '^strict-transport-security' && echo "ok   HSTS header" || { echo "FAIL no HSTS header (is APP_ENV=production and https on?)"; fail=1; }
exit $fail
