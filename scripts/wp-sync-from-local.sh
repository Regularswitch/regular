#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOCAL_PLUGINS="${LOCAL_WP_PLUGINS:-$HOME/Local Sites/regularswitch-wp/app/public/wp-content/plugins}"
DEST="$ROOT/wordpress/plugins"

if [[ ! -d "$LOCAL_PLUGINS/regular-cms" && ! -d "$LOCAL_PLUGINS/traducao" ]]; then
  echo "Plugin local não encontrado em: $LOCAL_PLUGINS/regular-cms (ou traducao/)"
  echo "Ajuste LOCAL_WP_PLUGINS se o caminho do Local for outro."
  exit 1
fi

mkdir -p "$DEST"

if [[ -d "$LOCAL_PLUGINS/regular-cms" ]]; then
  rsync -a --delete "$LOCAL_PLUGINS/regular-cms/" "$DEST/regular-cms/"
else
  rsync -a --delete "$LOCAL_PLUGINS/traducao/" "$DEST/regular-cms/"
fi

echo "OK — Regular CMS sincronizado para wordpress/plugins/regular-cms/"
