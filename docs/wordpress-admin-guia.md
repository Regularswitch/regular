# Tutorial — novo admin WordPress (Regular Switch)

Guia para editar o conteúdo do site no WordPress. O WP é só o **CMS**; o site público é o Next.js, que lê tudo pela API.

**Plugin:** Tradução **v1.1.0** (e Api Rest Etc Extension)  
**Para quem:** quem edita textos, imagens, projetos e menus no painel.

---

## Ambientes

| Ambiente | Admin | Site para conferir |
|----------|-------|--------------------|
| **Local** | `http://regularswitch-wp.local/wp-admin` | Next local |
| **Staging** | `https://staging-wp.regularswitch.com/wp-admin` | Preview (ex.: `dev.regularswitch.com.br`) |
| **Produção** | `https://wp.regularswitch.com/wp-admin` | `regularswitch.com` |

**Regra de ouro:** edite no **staging**, confira o preview, depois repita em **produção**.

---

## O que mudou no admin novo

Antes havia fluxos legados (item **Tradutor**, JSON no editor, campos soltos). Agora:

1. Cada página do site tem um **menu próprio** no lateral (Intro, Sobre Nós, Capacidades…).
2. Conteúdo editável fica em **caixas com campos nomeados** — não edite JSON no editor clássico.
3. Inglês e português são **dois posts** (`en` / `pt`), ligados pela coluna **Language**.
4. Imagens de topo (Sobre, Educação, Contato) ficam num lugar só: **Heroes**.
5. Projetos têm caixa **Conteúdo do Projeto (site)** (hero, logo, acordeão, galeria). O editor principal do WP está **desligado** nos projetos.

Se o admin não bater com este guia, o plugin **Tradução** no servidor provavelmente está desatualizado — faça deploy do ZIP (`./scripts/wp-package-plugins.sh`). Detalhes: [wordpress-staging.md](./wordpress-staging.md).

---

## Menu lateral (mapa rápido)

Ordem típica (entre separadores):

| Menu | O que controla no site |
|------|------------------------|
| **Intro** | Texto grande da home |
| **Visual da home** | Cores do blob 3D (vale EN + PT) |
| **Interface do site** | Labels (“Projetos Selecionados”, “Últimos”…) |
| **Heroes** | Imagens de topo de Sobre, Educação e Contato |
| **Sobre Nós** | Página About |
| **Página de projetos** | Título da listagem `/projects` |
| **Capacidades** | Página Capabilities |
| **Educação** | Página Education |
| **Marcas** | Logos do carrossel |
| **Contato** | Página Contact |
| **Footer** | Rodapé |
| **Projects** | Cada projeto (`/project/{slug}`) |
| **Aparência → Menus** | Links do header (EN e PT) |

> **Heroes** abre direto a tela de edição — não há listagem.

---

## Conceitos que você precisa saber

### Dois idiomas (EN / PT)

Na maioria das páginas existem **dois posts**:

| Post | Alimenta |
|------|----------|
| slug `en` | site em inglês (`/…`) |
| slug `pt` | site em português (`/PT/…`) |

Na lista, a coluna **Language** mostra **EN** e **PT**. Clique para abrir (ou criar) a tradução vinculada.

**Importante em Projects:** cada projeto EN deve apontar para a **sua** tradução PT. Se vários projetos tiverem PT ligado ao mesmo post (ex.: Terrô), o site em português mostra o **mesmo título** em cards diferentes. Sempre confira o vínculo.

### Slug automático

Nos CPTs de página, **não** mude o slug para algo diferente de `en` ou `pt`. Ao salvar, o plugin define o permalink (ex.: `/about/en/`). O box lateral **Slug / idioma (automático)** confirma isso.

Projetos individuais **não** usam `en`/`pt` no slug — cada um tem o próprio (ex.: `piktiz`).

### Campos vazios

Campo vazio → o site usa o **texto padrão** do Next.js. Para voltar ao padrão, apague o conteúdo e salve.

### Imagens compartilhadas vs. por idioma

| O quê | Onde |
|-------|------|
| Hero Sobre / Educação / Contato | **Heroes** (imagem e/ou vídeo, EN+PT) |
| Textos, acordeões, headlines | Post `en` ou `pt` de cada página |
| Cores do blob | **Visual da home** (único) |
| Hero e galeria de um projeto | No próprio projeto (e na tradução PT, se existir) |

### Editor com negrito (botão **B**)

Campos rich text têm toolbar com **B**. Digite e formate ali — **não cole HTML** copiado do site.

### Acordeões e blocos

Em Sobre, Capacidades, Educação, Contato e Projetos:

- **+ Adicionar seção** (ou **+ Adicionar bloco** / **+ Adicionar imagem**)
- **Remover** para excluir
- Sempre **Atualizar** / **Publicar** no final — as listas só salvam no envio do formulário

---

## Fluxo padrão (qualquer alteração)

```
1. Login no staging (ou local)
2. Abra o menu certo (tabela acima)
3. Escolha o post en ou pt
4. Edite os campos da caixa
5. Atualizar / Publicar
6. Confira no site (EN e /PT)
7. Se ok → repita em produção
```

### Criar / editar tradução PT

1. Salve a versão **EN**
2. Na lista, coluna **Language** → clique **PT**
3. Preencha os campos em português
4. Salve
5. Visite `/PT/...` no site

---

## Tutorial por área

### 1. Home — Intro

**Menu:** Intro → post `en` ou `pt`

Caixa **Conteúdo da Intro (home):**

| Campo | No site |
|-------|---------|
| **Título grande (headline)** | Texto principal abaixo do blob |
| **Parágrafo abaixo (body)** | Texto menor sob o título |

Use **B** para destacar palavras.

---

### 2. Home — cores do blob

**Menu:** Visual da home

| Campo | Função |
|-------|--------|
| Cor principal 1 / 2 | Cores dominantes da animação |
| Paleta | Uma cor por linha (ou separadas por vírgula) |

Vale para **inglês e português** ao mesmo tempo. Não há posts `en`/`pt` aqui.

---

### 3. Heroes (Sobre, Educação, Contato)

**Menu:** Heroes

Para cada página (Sobre Nós, Educação, Contato):

- **Imagem** — poster / fallback
- **Vídeo (mp4)** — opcional; se preenchido, tem **prioridade** no site (autoplay, muted, loop)

A mídia é **compartilhada entre EN e PT**. Em Contato, o hero só aparece se houver imagem ou vídeo.

---

### 4. Menu do header

**Menu:** Aparência → Menus

Dois locais:

- **Header — English**
- **Header — Português**

Monte a ordem com páginas ou **Links personalizados**. Use caminhos do Next.js **sem** prefixo `/PT` (o site resolve o idioma):

- `/projects`
- `/capabilities`
- `/education`
- `/about-us`
- `/contact`

Labels de seções (“Últimos”, “Veja mais…”) **não** ficam aqui — vão em **Interface do site**.

---

### 5. Interface do site (labels)

**Menu:** Interface do site → `en` ou `pt`

Cada post edita **só um idioma**. Exemplos:

| PT | EN |
|----|-----|
| Projetos Selecionados | Selected Projects |
| Últimos | The Latest |
| Marcas | Brands marquee |
| Veja mais projetos / trabalhos | See more projects / work |
| Novidades (label, título, subtítulo) | What's New (…) |

---

### 6. Sobre Nós

**Menu:** Sobre Nós → `en` ou `pt`

| Campo | Função |
|-------|--------|
| **Headline** | Título da página |
| **Texto introdutório** | Texto ao lado do título (desktop) |
| **Seções do acordeão** | Título, texto e **imagem lateral** por seção |

Hero: só em **Heroes**. A grade de “últimos projetos” vem de **Projects**, não desta tela.

---

### 7. Capacidades

**Menu:** Capacidades → `en` ou `pt`

| Campo | Função |
|-------|--------|
| **Headline** | Título no topo |
| **Seções** | Título, texto e imagem por item do acordeão |

Sem hero de página — só headline + acordeão.

---

### 8. Educação

**Menu:** Educação → `en` ou `pt`

| Campo | Função |
|-------|--------|
| **Headline** | Título abaixo do hero |
| **Seções** | Título e texto (sem imagem lateral) |

Hero: **Heroes**. A grade de projetos usa itens de **Projects** na categoria **education**.

---

### 9. Contato

**Menu:** Contato → `en` ou `pt`

| Campo | Função |
|-------|--------|
| **Headline** | Título da página |
| **Blocos** | Colunas (título + conteúdo) — **+ Adicionar bloco** |

Hero: **Heroes**.

---

### 10. Página de projetos (listagem)

**Menu:** Página de projetos → `en` ou `pt`

| Campo | Função |
|-------|--------|
| **Título da seção** | Título em `/projects` |
| **Headline** | Destaque / subtítulo |
| **Mensagem quando não há projetos** | Estado vazio |

Os cards vêm de **Projects**.

---

### 11. Projects (cada projeto)

**Menu:** Projects → abra um projeto

Cada um tem **slug próprio**. Para português: coluna **Language → PT** (confira se o vínculo é o post certo).

#### Caixa “Conteúdo do Projeto (site)”

| Grupo | Campos |
|-------|--------|
| **Hero (topo)** | **Imagem de fundo** (1:1 mobile, 16:9 desktop) · **Logo** (sobre a imagem no desktop; oculta no mobile) |
| **Acordeão (coluna direita)** | Seções com **título** + texto — **+ Adicionar seção** |
| **Galeria (fotos abaixo)** | **+ Adicionar imagem** (ordem = ordem no site) |

#### Barra lateral

| Campo | Função |
|-------|--------|
| **Resumo** | Texto à esquerda na página do projeto |
| **Imagem destacada** | Fallback do logo, se o campo Logo estiver vazio |
| **Categorias** | Education, Motion Design, etc. (filtram listagens) |

> Não use o editor principal do WordPress nos projetos — ele está desativado. Tudo que aparece no site está na caixa acima + Resumo.

#### Checklist de um projeto novo

1. Crie o projeto em EN (título + slug)
2. Preencha hero, logo, resumo, acordeão, galeria, categorias
3. Publique
4. Language → **PT** → preencha a tradução (título PT + campos)
5. Confira `/project/{slug}` e `/PT/project/{slug}`
6. Confira se o card na home mostra o **título correto** em PT (não o de outro projeto)

---

### 12. Marcas

**Menu:** Marcas

Cada marca: **título** + **imagem destacada** (logo). Ajuste a ordem pelos atributos de página, se disponível.

---

### 13. Footer

**Menu:** Footer → `en` ou `pt`

| Grupo | Campos |
|-------|--------|
| **Marca** | Texto grande (ex.: REGULARSWITCH) |
| **Coluna 1–3** | Título, subtítulo, link |
| **Links legais** | Marca legal, privacidade (texto + link), cookies (texto + link) |

---

## Mídia

- **Selecionar imagem** abre a biblioteca do WordPress
- Upload: **Mídia → Adicionar**
- Prefira JPG/WebP otimizados; heroes de página funcionam melhor em proporção larga

---

## Atualizar core / plugins do WordPress

1. **Backup** (All-in-One WP Migration → Exportar)
2. Menu **Atualizações**
3. Core → plugins → tema
4. Teste a API e algumas páginas do site

> Atualizar o WordPress **não** envia o plugin **Tradução** do repositório. Mudanças no código exigem deploy do ZIP (`./scripts/wp-package-plugins.sh`).

---

## O que evitar

| Não faça | Motivo |
|----------|--------|
| Editar produção sem testar no staging | Risco no site ao vivo |
| Colar HTML do front no editor | Quebra o layout |
| Criar posts `en`/`pt` duplicados na mão | Use a coluna **Language** |
| Mudar slug de páginas bilíngues | O sistema espera `en` ou `pt` |
| Confiar no item **Tradutor** (legado) | Fluxo atual = posts EN/PT + Language |
| Ligar vários projetos EN ao mesmo PT | Títulos errados na home PT |

---

## Problemas comuns

**Salvei e não aparece no site**

- Post **Publicado** (não rascunho)?
- Idioma certo (`en` vs `pt`)?
- Preview Vercel apontando `API` para o WP certo?
- Cache (LiteSpeed → Purge All), se houver

**Acordeão / galeria não salvou**

- Clique em **Atualizar** depois de editar
- Não feche a aba antes do salvamento terminar

**Hero de Sobre / Educação / Contato não mudou**

- Edite em **Heroes**, não no post da página

**Na home PT vários projetos mostram o mesmo título (ex.: Terrô)**

- Em **Projects**, abra cada projeto EN e confira **Language → PT**
- O PT deve ser a tradução **daquele** projeto; corrija ou recrie o vínculo

**Footer vazio**

- Posts Footer com slugs `en` e `pt` preenchidos?

**Menu do header desatualizado**

- **Aparência → Menus**, não Interface do site

**Admin diferente deste tutorial**

- Confira se o plugin Tradução é **v1.1.0** (Plugins no WP, ou texto de ajuda na caixa do projeto)

---

## Referência rápida — URL ↔ menu WP

| Página | Rota Next.js | Menu WP |
|--------|--------------|---------|
| Home | `/` e `/PT` | Intro, Visual da home, Interface |
| Projetos | `/projects` | Página de projetos + Projects |
| Capacidades | `/capabilities` | Capacidades |
| Educação | `/education` | Educação + Heroes |
| Sobre | `/about-us` | Sobre Nós + Heroes |
| Contato | `/contact` | Contato + Heroes |
| Projeto | `/project/{slug}` | Projects |

---

## Mais documentação

- Deploy e staging: [wordpress-staging.md](./wordpress-staging.md)
- Plugins no repositório: [wordpress/README.md](../wordpress/README.md)
