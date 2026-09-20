#!/usr/bin/env bash
set -euo pipefail

# Uso:
#   ./scripts/prepare-wp-sql-for-staging.sh u762864204_rsw.sql
# Gera: u762864204_rsw-staging.sql (corrigido + URLs do staging)

INPUT="${1:-}"
if [[ -z "$INPUT" || ! -f "$INPUT" ]]; then
  echo "Uso: $0 caminho/para/dump.sql"
  exit 1
fi

OUTPUT="${INPUT%.sql}-staging.sql"
STAGING_URL="${STAGING_URL:-https://staging-wp.regularswitch.com}"

echo "Entrada:  $INPUT"
echo "Saída:    $OUTPUT"
echo "URL WP:   $STAGING_URL"

sed \
  -e "s/DEFAULT x AS \`\([0-9a-f]*\)\`/DEFAULT x'\1'/g" \
  -e "s|https://regularswitch-wp.local|${STAGING_URL}|g" \
  -e "s|http://regularswitch-wp.local|${STAGING_URL}|g" \
  -e "s|https://wp.regularswitch.com|${STAGING_URL}|g" \
  -e "s|http://wp.regularswitch.com|${STAGING_URL}|g" \
  -e "s|https://regularswitch.com|${STAGING_URL}|g" \
  -e "s|http://regularswitch.com|${STAGING_URL}|g" \
  "$INPUT" > "$OUTPUT"

echo "OK — importe $OUTPUT no phpMyAdmin do banco do staging."
