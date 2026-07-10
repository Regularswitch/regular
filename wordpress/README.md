# WordPress — plugins versionados

Cópia dos plugins customizados usados pelo headless Next.js.

## Estrutura

```
wordpress/plugins/
├── traducao/     # CPTs, tradução EN/PT, campos footer, capacidades, interface e projeto
└── api-etc/      # REST api-etc/v2/all-posts (metas de projeto)
```

## Convenção de slugs (EN/PT)

Posts editáveis seguem o permalink **`{tipo}/{idioma}/`**:

| Tipo | Exemplo permalink | `post_name` no WP |
|------|-------------------|-------------------|
| CPT intro | `/intro/en/` | `en` |
| CPT footer | `/footer/pt/` | `pt` |
| CPT capabilities | `/capabilities/en/` | `en` |
| Página contact | `/contact/en/` | `en` (filha de `contact`) |

O plugin **Tradução** (`slug-language.php`) define o slug automaticamente ao salvar, com base no título (`EN`/`PT`) ou no vínculo de tradução. No admin aparece um box lateral com o permalink final.

Campos com destaque em negrito (Intro, Capacidades, projetos) usam **editor rich text** (`rich-text-fields.php`) — botão **B** em vez de digitar `<strong>`.

Projetos individuais (`project`) mantêm slug próprio por projeto — não usam `en`/`pt`.

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

- Guia de staging: [docs/wordpress-staging.md](../docs/wordpress-staging.md)
- **Guia do admin (como editar o site):** [docs/wordpress-admin-guia.md](../docs/wordpress-admin-guia.md)
