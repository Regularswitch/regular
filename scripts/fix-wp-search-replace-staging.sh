#!/usr/bin/env bash
# Gera rs-search-replace-staging.php para upload temporário na raiz do WordPress (staging).
#
# Uso:
#   ./scripts/fix-wp-search-replace-staging.sh > /tmp/rs-search-replace-staging.php
#   # envie para public_html/staging-wp/rs-search-replace-staging.php
#   # abra no browser SEM key → copie a key na mensagem 403
#   # dry run: ?key=...&dry=1  depois aplicar sem dry=1
#   # apague o arquivo do servidor
#
# Variáveis opcionais:
#   STAGING_URL=https://staging-wp.regularswitch.com
#   LOCAL_URL=http://regularswitch-wp.local

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TEMPLATE="$ROOT/scripts/rs-search-replace-staging.php.template"

STAGING_URL="${STAGING_URL:-https://staging-wp.regularswitch.com}"
LOCAL_HTTP="${LOCAL_URL:-http://regularswitch-wp.local}"
LOCAL_HTTPS="https://regularswitch-wp.local"

if [[ ! -f "$TEMPLATE" ]]; then
  echo "Template não encontrado: $TEMPLATE" >&2
  exit 1
fi

sed \
  -e "s|__STAGING_URL__|${STAGING_URL}|g" \
  -e "s|__LOCAL_HTTP__|${LOCAL_HTTP}|g" \
  -e "s|__LOCAL_HTTPS__|${LOCAL_HTTPS}|g" \
  "$TEMPLATE"

echo "" >&2
echo "Upload: rs-search-replace-staging.php → raiz do staging-wp/" >&2
echo "1) Abra https://staging-wp.regularswitch.com/rs-search-replace-staging.php (sem key)" >&2
echo "2) Copie a key da resposta 403" >&2
echo "3) Dry run: ...?key=KEY&dry=1" >&2
echo "4) Aplicar: ...?key=KEY" >&2
echo "5) Apague o arquivo do servidor" >&2
