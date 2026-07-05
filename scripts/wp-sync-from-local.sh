#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOCAL_PLUGINS="${LOCAL_WP_PLUGINS:-$HOME/Local Sites/regularswitch-wp/app/public/wp-content/plugins}"
DEST="$ROOT/wordpress/plugins"

if [[ ! -d "$LOCAL_PLUGINS/traducao" ]]; then
  echo "Plugin local não encontrado em: $LOCAL_PLUGINS/traducao"
  echo "Ajuste LOCAL_WP_PLUGINS se o caminho do Local for outro."
  exit 1
fi

mkdir -p "$DEST"
rsync -a --delete "$LOCAL_PLUGINS/traducao/" "$DEST/traducao/"
rsync -a --delete "$LOCAL_PLUGINS/api-etc/" "$DEST/api-etc/"

echo "OK — plugins sincronizados para wordpress/plugins/"
