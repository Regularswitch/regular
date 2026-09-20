#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/wordpress/plugins"
OUT="$ROOT/wordpress/dist"
ZIP="$OUT/wp-plugins.zip"

if [[ ! -d "$SRC/regular-cms" ]]; then
  echo "Plugin não encontrado em: $SRC/regular-cms"
  exit 1
fi

mkdir -p "$OUT"
rm -f "$ZIP"

(cd "$SRC" && zip -rq "$ZIP" regular-cms)

echo "OK — $ZIP"
echo "Upload: extraia em wp-content/plugins/ no servidor."
echo "Migração: remova pastas legadas traducao/ e api-etc/ se ainda existirem."
