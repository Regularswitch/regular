# Regular CMS — Admin (Design System)

Documentação do **novo admin** do WordPress headless da Regular Switch.  
Escopo: **só o painel WP**. O front Next.js não muda por causa do chrome do CMS.

**Versão atual do plugin:** ver `plugin-meta.php` (`RS_PLUGIN_VERSION`, hoje `1.5.45`).

Manual para o **time de conteúdo** (como publicar no site): [MANUAL-CONTEUDO.md](./MANUAL-CONTEUDO.md).

---

## Princípios

1. **Apresentação ≠ dados** — UI nova (classes, helpers, CSS) **não** renomeia meta keys (`rs_*`), nonces nem rotas REST.
2. **Post único bilíngue** — a maioria das seções é um post; EN/PT nas abas (não dois posts gêmeos).
3. **Publicar nativo** — a coluna lateral de Publicar/Imagem destacada permanece no layout WP (direita).
4. **Outfit não entra no admin** — tipografia do CMS é system UI; a fonte da marca fica no site público.

---

## Mapa do menu

| Menu | Conteúdo |
|------|----------|
| **Conteúdo** | Projetos, Intro, Sobre, Página de projetos, Capacidades, Educação, Marcas, Contato |
| **Sistema** | Visual da home, Interface do site, Footer, Privacidade & Cookies, Menus do header, Design System (preview) |
| **WP nativo** | Mídia, Usuários, etc. (inalterados) |

### Onde achar itens frequentes

| Precisa editar… | Caminho |
|-----------------|---------|
| Home (headline) | Conteúdo → Intro |
| Projetos | Conteúdo → Projetos |
| **Categorias de projeto** | Conteúdo → Projetos → **Categorias** (`project-category`) |
| Capacidades / FAQ | Conteúdo → Capacidades |
| SEO title/description | Metabox **SEO** em cada CPT de conteúdo |
| Schema da organização | Sistema → Interface do site → metabox Schema |
| Tokens / componentes | Sistema → Design System |

Hubs: `admin.php?page=rs-content` e `admin.php?page=rs-system`.

---

## Tema claro / escuro

- Toggle na **admin bar** (canto superior).
- Preferência salva no browser (`html.rs-theme-light` / `html.rs-theme-dark`).
- Tokens em `assets/rs-design-system.css`.

### Paleta de marca (admin)

| Token | Hex | Uso |
|-------|-----|-----|
| Ink | `#232323` | Texto |
| Accent | `#0067FF` | Primário / foco |
| Muted | `#6E7174` | Secundário |
| Line | `#E7E5E5` | Bordas |
| Canvas | `#F9F9F9` | Fundo |

---

## Como editar conteúdo

### Abas EN / PT

- Quase todas as seções: abas **English** / **Português** no metabox.
- Mídia compartilhada (hero, logo, galeria) costuma ficar **fora** das abas ou na aba Mídia (Projeto).
- Projeto: abas **Geral · English · Português · Mídia**.

### Rich text

- Use o botão **B** do TinyMCE para negrito (não digite `<strong>`).
- No site, o texto corrido usa `font-weight: 300`; negrito `500`. Spans com `font-weight: 400` inline do WP são neutralizados no front.

### Mídia

- Campos de imagem/vídeo usam **dropzone** DS: clique abre a biblioteca WP; arrastar arquivo também envia (quando o browser/nonce permitem).
- Galerias de projeto/education: multi-select na biblioteca + arrastar para ordenar.

### SEO

- Metabox **SEO (title & meta description)** com abas EN/PT e prévia tipo Google.
- Categorias: campos H1 / intro / SEO no formulário do termo.
- Schema global: Sistema → Interface do site.

### Publicar

- Use o botão **Publicar / Atualizar** nativo da barra lateral.
- Em Projeto, campos JSON grandes vão no início do formulário (mitiga `max_input_vars` em hosts como Hostinger).

---

## Arquitetura (devs)

### Camadas

| Camada | Arquivos | Função |
|--------|----------|--------|
| Tokens + componentes | `assets/rs-design-system.css`, `assets/rs-design-system.js`, `admin-design-system.php` | DS light/dark + preview |
| Shell | `admin-shell.php`, `assets/rs-admin-shell.css` | Menus Conteúdo/Sistema, topbar, list tables |
| Helpers UI | `ui-helpers.php` | `rs_ds_*` — só markup |
| Metaboxes | `*-fields.php` | Campos + save (contratos estáveis) |
| Chrome legado | `metabox-ui.php`, `assets/rs-metabox-ui.*` | Abas/acordeão JS (`data-rs-tabs`) |
| Dashboard | `admin-dashboard.php`, `assets/rs-admin-dashboard.css` | Widgets do painel |

Carregamento: `load.php` (helpers **antes** do design system).

### Helpers `rs_ds_*`

```php
rs_ds_alert('Texto', 'info');           // info|success|warning|danger
rs_ds_fieldset_open('Legenda');         // + opcional $extra_class
rs_ds_fieldset_close();
rs_ds_help('Dica');
rs_ds_locale_tabs_open(['en' => 'English', 'pt' => 'Português'], 'en');
rs_ds_locale_panel_open('en', true);
rs_ds_locale_panel_close();
rs_ds_locale_tabs_close();
rs_ds_card_open('Título', 'Subtítulo');
rs_ds_card_close();
```

**Regra:** helpers só emitem classes. Manter `data-rs-tabs`, `data-tab`, names e IDs usados pelo JS/save.

Padrão de metabox:

```php
echo '<div class="rs-ds-editor rs-*-editor">';
rs_ds_alert('…', 'info');
rs_ds_locale_tabs_open(…);
// fieldsets + campos
rs_ds_locale_tabs_close();
echo '</div>';
```

### Classes CSS úteis

| Classe | Uso |
|--------|-----|
| `.rs-ds-editor` | Wrapper do metabox |
| `.rs-ds-fieldset` | Seção interna |
| `.rs-ds-field` / `.rs-ds-label` / `.rs-ds-input` / `.rs-ds-textarea` | Formulário |
| `.rs-ds-dropzone` | Upload de mídia |
| `.rs-ds-locale-tabs` | Abas EN/PT |
| `.rs-ds-serp*` | Prévia SEO |
| `.rs-ds-badge` | Badges (ex.: coluna Language) |
| `body.rs-shell-screen` | Telas sob o shell |

### O que **não** alterar sem migração

- Meta keys `rs_*` / `rs_*_i18n`
- Nonces e handlers `save_post_*`
- REST `api-etc/v2/all-posts`, `rs/v1/*`, campos `*_data` / `seo_data`
- IDs de campos usados por `project-admin.js` / `rs-metabox-ui.js`

---

## Deploy do plugin

1. Código versionado em `wordpress/plugins/regular-cms/`.
2. Sync Local (dev):

```bash
rsync -a wordpress/plugins/regular-cms/ \
  ~/Local\ Sites/regularswitch-wp/app/public/wp-content/plugins/regular-cms/
```

3. Staging/produção: ZIP via `scripts/wp-package-plugins.sh` ou rsync no servidor (ver `wordpress/README.md`).
4. Após upload, conferir versão em Sistema → Design System ou no painel.

**Subir só o redesign de admin não apaga nem reescreve conteúdo** — só muda UI (e CSS do front se o deploy do Next incluir o fix de `font-weight` nos spans).

---

## Checklist de QA (admin)

- [ ] Menu Conteúdo / Sistema e hubs
- [ ] Toggle light/dark
- [ ] Intro: abas EN/PT + salvar
- [ ] Capacidades: seções + FAQ
- [ ] Projeto: Geral / EN / PT / Mídia + galeria
- [ ] SEO: prévia atualiza ao digitar
- [ ] Dropzone: selecionar e remover mídia
- [ ] Education: instituições / galerias
- [ ] Categorias: editar termo + SEO
- [ ] Publicar permanece à direita

---

## Histórico resumido

| Fase | Entrega |
|------|---------|
| 1 | Auditoria (metaboxes PHP + TinyMCE + `wp.media`) |
| 2 | Design System + preview |
| 3 | Shell Conteúdo/Sistema |
| 4 | Metaboxes no DS (piloto → todos) + SEO UX + dropzone + polish Education |

Dúvidas de contrato API / i18n: ver `README.md` deste plugin e `wordpress/README.md`.
