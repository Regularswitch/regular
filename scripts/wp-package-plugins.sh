#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/wordpress/plugins"
OUT="$ROOT/wordpress/dist"
ZIP="$OUT/wp-plugins.zip"

if [[ ! -d "$SRC/traducao" ]]; then
  echo "Execute primeiro: ./scripts/wp-sync-from-local.sh"
  exit 1
fi

mkdir -p "$OUT"
rm -f "$ZIP"

(cd "$SRC" && zip -rq "$ZIP" traducao api-etc)

echo "OK — $ZIP"
echo "Upload: extraia em wp-content/plugins/ no servidor (staging ou produção)."
