# Regular CMS

Plugin único do WordPress headless da Regular Switch.

**Pasta:** `regular-cms/` · **Arquivo principal:** `regular-cms.php`

## O que inclui

- CPTs do site (project, intro, contact, education, capabilities, about, etc.)
- Meta boxes e UI admin (`metabox-ui`, `rs-admin-chrome`)
- Conteúdo bilíngue EN/PT (`slug-language`, `project-i18n`, `rest-translate`)
- REST `GET /wp-json/api-etc/v2/all-posts` (`rest-all-posts.php`)
- Proxy de tradução legado (`proxy.php`) e sync de mídia

## Estrutura

| Arquivo | Função |
|---------|--------|
| `regular-cms.php` | Header WP + bootstrap |
| `plugin-meta.php` | Versão, nome, helpers |
| `load.php` | Ordem de carregamento dos módulos |
| `*-fields.php` | Meta boxes por CPT/página |
| `rest-all-posts.php` | Rota legada consumida pelo Next.js |

## Migração de plugins antigos

No WP Admin, após deploy:

1. Ative **Regular CMS** (`regular-cms/regular-cms.php`)
2. Desative e remova pastas legadas:
   - `wp-content/plugins/traducao/`
   - `wp-content/plugins/api-etc/`

Projetos usam post único bilíngue (`rs_project_i18n`). Outros CPTs (footer, contact, etc.) continuam com pares EN/PT.

## Versão

Definida em `plugin-meta.php` (`RS_PLUGIN_VERSION`).

## Deploy

Ver [wordpress/README.md](../../README.md) e `scripts/wp-package-plugins.sh`.
