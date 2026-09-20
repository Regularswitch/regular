# Regular CMS

Plugin único do WordPress headless da Regular Switch.

**Pasta:** `regular-cms/` · **Arquivo principal:** `regular-cms.php`

## Documentação do admin

- **Time de conteúdo** (como publicar no site): → **[MANUAL-CONTEUDO.md](./MANUAL-CONTEUDO.md)**
- **Time técnico** (Design System, shell, helpers): → **[ADMIN.md](./ADMIN.md)**

## O que inclui

- CPTs do site (project, intro, contact, education, capabilities, about, etc.)
- Admin shell + Design System light/dark (`admin-shell`, `admin-design-system`, `ui-helpers`)
- Meta boxes e UI (`*-fields.php`, `metabox-ui`, dropzone de mídia)
- Conteúdo bilíngue EN/PT (`slug-language`, `project-i18n`, `rest-translate`)
- REST `GET /wp-json/api-etc/v2/all-posts` (`rest-all-posts.php`)
- Proxy de tradução legado (`proxy.php`) e sync de mídia

## Estrutura

| Arquivo | Função |
|---------|--------|
| `regular-cms.php` | Header WP + bootstrap |
| `plugin-meta.php` | Versão, nome, helpers |
| `load.php` | Ordem de carregamento dos módulos |
| `ADMIN.md` | Documentação do admin (DS + shell) |
| `ui-helpers.php` | Helpers `rs_ds_*` (só apresentação) |
| `admin-shell.php` | Menus Conteúdo / Sistema |
| `admin-design-system.php` | Preview do Design System |
| `*-fields.php` | Meta boxes por CPT/página |
| `rest-all-posts.php` | Rota legada consumida pelo Next.js |

## Migração de plugins antigos

No WP Admin, após deploy:

1. Ative **Regular CMS** (`regular-cms/regular-cms.php`)
2. Desative e remova pastas legadas:
   - `wp-content/plugins/traducao/`
   - `wp-content/plugins/api-etc/`

Projetos e seções migradas usam **post único bilíngue** (EN/PT nas abas). Marcas não têm tradução.

## Versão

Definida em `plugin-meta.php` (`RS_PLUGIN_VERSION`).

## Deploy

Ver [wordpress/README.md](../../README.md) e `scripts/wp-package-plugins.sh`.
