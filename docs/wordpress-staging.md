# WordPress Staging — Regular Switch

Objetivo: testar plugins e conteúdo **sem afetar** `wp.regularswitch.com` (produção).

## Arquitetura

```
┌─────────────────┐     API      ┌──────────────────────────────┐
│  Next.js local  │ ───────────► │ regularswitch-wp.local       │
└─────────────────┘              └──────────────────────────────┘

┌─────────────────┐     API      ┌──────────────────────────────┐
│ Vercel (branch  │ ───────────► │ staging-wp.regularswitch.com │
│ staging/preview)│              │  ← STAGING WordPress         │
└─────────────────┘              └──────────────────────────────┘

┌─────────────────┐     API      ┌──────────────────────────────┐
│ Vercel (main /  │ ───────────► │ wp.regularswitch.com         │
│ produção)       │              │  ← PRODUÇÃO (não mexer até OK) │
└─────────────────┘              └──────────────────────────────┘
```

---

## Parte 1 — Criar o WordPress de staging (Hostinger)

### 1.1 Subdomínio

1. Acesse **hPanel** → **Domínios** → **Subdomínios**
2. Crie: `staging-wp.regularswitch.com`
3. Document root sugerido: `public_html/staging-wp` (ou o que a Hostinger indicar)

Aguarde o DNS propagar (minutos a algumas horas).

### 1.2 Instalar WordPress no subdomínio

**Opção A — Clonar produção (recomendado)**

1. No WP de **produção**, instale **All-in-One WP Migration**
2. Exporte o site (arquivo `.wpress`)
3. No subdomínio `staging-wp`, instale WordPress limpo + o mesmo plugin
4. Importe o `.wpress`
5. O plugin atualiza URLs automaticamente para `staging-wp.regularswitch.com`

**Opção B — Clonar do Local**

1. No Local (`regularswitch-wp.local`), exporte com All-in-One WP Migration
2. Importe no subdomínio staging
3. Confirme em **Configurações → Gerais**:
   - URL do WordPress: `https://staging-wp.regularswitch.com`
   - URL do site: `https://staging-wp.regularswitch.com`

### 1.3 Validar REST API

```bash
curl -s "https://staging-wp.regularswitch.com/wp-json/wp/v2/project?per_page=1" | head
curl -s "https://staging-wp.regularswitch.com/wp-json/api-etc/v2/all-posts" | head
```

Se retornar JSON, o staging está acessível.

---

## Parte 2 — Deploy dos plugins no staging

No repositório Next.js:

```bash
./scripts/wp-package-plugins.sh
```

1. Envie `wordpress/dist/wp-plugins.zip` para a Hostinger (FTP ou Gerenciador de arquivos)
2. Extraia em `wp-content/plugins/`
3. No admin do **staging**: **Plugins** → ative **Tradução** e **Api Rest Etc Extension**
4. Teste:
   ```bash
   curl -s "https://staging-wp.regularswitch.com/wp-json/wp/v2/footer?per_page=1"
   ```
   Deve incluir `footer_data` na resposta.

> **Produção:** só repita este passo em `wp.regularswitch.com` depois de validar tudo no staging.

---

## Parte 3 — Vercel (Next.js aponta para staging)

No painel **Vercel** → projeto → **Settings** → **Environment Variables**:

| Variável | Preview | Production |
|----------|---------|------------|
| `API` | `https://staging-wp.regularswitch.com` | `https://wp.regularswitch.com` |
| `BASE` | URL do preview (ou deixe Vercel inferir) | `https://regularswitch.com` |

- **Preview** = deploys da branch `staging` e PRs
- **Production** = branch `main` / domínio ao vivo

Via CLI (se tiver `vercel` linkado):

```bash
vercel env add API preview
# valor: https://staging-wp.regularswitch.com

vercel env add API production
# valor: https://wp.regularswitch.com
```

Faça um redeploy da branch `staging` após salvar as variáveis.

---

## Parte 4 — Fluxo de trabalho do dia a dia

```
1. Editar plugins em Local OU em wordpress/plugins/ no repo
2. ./scripts/wp-sync-from-local.sh  (se mudou no Local)
3. ./scripts/wp-package-plugins.sh
4. Upload ZIP → staging-wp apenas
5. Testar conteúdo no admin staging + site Vercel preview
6. git push staging → Vercel rebuild
7. Quando OK → mesmo ZIP em produção + merge para main
```

---

## Parte 5 — Conteúdo e tradução EN/PT no staging

No admin do **staging** (não produção):

1. **Footer** → post EN → preencha campos → Salvar
2. Na lista, coluna **Language** → clique **PT** → preencha versão PT
3. **Projects** → use a caixa **Conteúdo do Projeto (site)**

O site em `/PT` no preview da Vercel usará `?translate=PT` na API do staging.

---

## Checklist rápido

- [ ] Subdomínio `staging-wp.regularswitch.com` criado
- [ ] WordPress instalado / importado no subdomínio
- [ ] Plugins enviados e ativados no staging
- [ ] REST API respondendo
- [ ] Vercel `API` em Preview = URL do staging
- [ ] Preview da Vercel carrega home, footer e projeto
- [ ] Só então: deploy plugins em produção

---

## Problemas comuns

**Imagens 404 no preview**  
Confirme `staging-wp.regularswitch.com` em `next.config.js` → `images.remotePatterns`.

**Footer vazio no preview**  
Crie posts no CPT Footer no admin do **staging** e preencha os campos.

**Mudou plugin em produção por engano**  
Restaure backup da Hostinger ou reimporte export de produção feito antes da mudança.
