# WordPress — plugins versionados

Cópia dos plugins customizados usados pelo headless Next.js.

## Estrutura

```
wordpress/plugins/
├── traducao/     # CPTs, tradução EN/PT, campos footer e projeto
└── api-etc/      # REST api-etc/v2/all-posts (metas de projeto)
```

## Sincronizar do Local WP

```bash
./scripts/wp-sync-from-local.sh
```

## Gerar ZIP para upload (Hostinger)

```bash
./scripts/wp-package-plugins.sh
```

Gera `wordpress/dist/wp-plugins.zip` — extraia em `wp-content/plugins/` no servidor.

## Ambientes

| Ambiente | URL WordPress |
|----------|----------------|
| Local | `http://regularswitch-wp.local` |
| **Staging** | `https://staging-wp.regularswitch.com` |
| Produção | `https://wp.regularswitch.com` |

Guia completo: [docs/wordpress-staging.md](../docs/wordpress-staging.md)
