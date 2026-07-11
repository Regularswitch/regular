# Guia do WordPress Admin — Regular Switch

Este documento ensina como usar o painel do WordPress para atualizar o conteúdo do site **Regular Switch**. O WordPress funciona como **CMS** (banco de conteúdo); o site público é o Next.js, que lê os dados via API.

## Ambientes

| Ambiente | URL do admin | Quando usar |
|----------|--------------|-------------|
| **Local** | `http://regularswitch-wp.local/wp-admin` | Desenvolvimento no seu computador |
| **Staging** | `https://staging-wp.regularswitch.com/wp-admin` | Testar antes de publicar |
| **Produção** | `https://wp.regularswitch.com/wp-admin` | Site ao vivo |

**Regra de ouro:** edite primeiro no **staging**, confira o preview do site, e só depois repita em **produção**.

---

## Visão geral do menu lateral

Os itens customizados do Regular Switch aparecem agrupados no menu (entre separadores). Ordem típica:

| Menu | O que edita |
|------|-------------|
| **Intro** | Texto grande da home (título + parágrafo) |
| **Visual da home** | Cores do blob 3D da home (EN + PT) |
| **Interface do site** | Labels de seções (“Últimos”, “Projetos Selecionados”…) |
| **Heroes** | Imagens de topo de Sobre, Educação e Contato (compartilhadas EN/PT) |
| **Sobre Nós** | Página About |
| **Página de projetos** | Título/headline da listagem `/projects` |
| **Capacidades** | Página Capabilities |
| **Educação** | Página Education |
| **Marcas** | Logos do carrossel de marcas |
| **Contato** | Página Contact |
| **Footer** | Rodapé do site |
| **Projects** | Cada projeto individual |
| **Aparência → Menus** | Links do menu do header (EN e PT) |

> **Heroes** abre direto a tela de edição das imagens — não há listagem separada.

---

## Conceitos importantes

### Dois idiomas (EN / PT)

A maioria das páginas tem **dois posts**: um com slug `en` e outro com `pt`.

- O post **EN** alimenta o site em inglês (`regularswitch.com/...`)
- O post **PT** alimenta o site em português (`regularswitch.com/PT/...`)

Na lista de posts, a coluna **Language** mostra links **EN** e **PT**. Clique em **PT** para abrir a versão em português vinculada (ou criar/editar a tradução).

### Slug automático

Não altere manualmente o slug para algo diferente de `en` ou `pt` nos CPTs de página. Ao salvar, o plugin define o permalink automaticamente (ex.: `/about/en/`, `/capabilities/pt/`). Um box lateral **Slug / idioma (automático)** confirma o permalink.

### Campos vazios

Se um campo ficar vazio, o site usa um **texto padrão** definido no código Next.js. Para “limpar” um override do WP e voltar ao padrão, apague o conteúdo e salve.

### Imagens compartilhadas vs. por idioma

| Tipo | Onde editar |
|------|-------------|
| Hero de Sobre, Educação, Contato | **Heroes** (uma imagem para EN e PT) |
| Textos, acordeões, headlines | Post `en` ou `pt` de cada página |
| Cores do blob da home | **Visual da home** (único para EN e PT) |

### Editor rich text

Campos com formatação usam um editor compacto com botão **B** para negrito. **Não cole HTML** copiado do site — digite ou formate pelo editor.

### Acordeões com várias seções

Em Sobre, Capacidades, Educação e Contato, use **+ Adicionar seção** (ou **+ Adicionar bloco**) para novos itens e **Remover** para excluir. Sempre clique em **Atualizar** / **Publicar** ao terminar — as seções são salvas no envio do formulário.

---

## Passo a passo por área

### 1. Home — Intro

**Menu:** Intro → abra o post `en` ou `pt`

| Campo | Aparece no site |
|-------|-----------------|
| **Título grande (headline)** | Texto principal abaixo do blob na home |
| **Parágrafo abaixo (body)** | Texto menor sob o título |

Use **B** para destacar palavras. Salve e confira a home no idioma correspondente.

---

### 2. Home — Cores do blob

**Menu:** Visual da home

| Campo | Função |
|-------|--------|
| Cor principal 1 / 2 | Cores dominantes da animação |
| Paleta | Lista de cores (uma por linha ou separadas por vírgula) |

Vale para **inglês e português** ao mesmo tempo.

---

### 3. Heroes das páginas

**Menu:** Heroes

Três imagens:

- **Sobre Nós**
- **Educação**
- **Contato**

Clique em **Selecionar imagem** → escolha na biblioteca ou envie nova → **Atualizar**. A mesma imagem aparece nos dois idiomas.

---

### 4. Menu do header

**Menu:** Aparência → Menus

Dois menus registrados:

- **Header — English**
- **Header — Português**

Arraste páginas ou **Links personalizados** para montar a ordem. URLs devem ser caminhos do site Next.js, por exemplo:

- `/projects`
- `/capabilities`
- `/education`
- `/about-us`
- `/contact`

Salve o menu. Labels e textos de botões em outras partes do site ficam em **Interface do site**.

---

### 5. Interface do site (labels)

**Menu:** Interface do site → post `en` ou `pt`

Cada post edita **só um idioma**. Campos típicos:

- Projetos Selecionados / Selected Projects
- Últimos / The Latest
- Marcas / Brands marquee
- Veja mais projetos / See more projects
- Novidades (label, título, subtítulo)

O menu de navegação **não** é editado aqui — use **Aparência → Menus**.

---

### 6. Sobre Nós

**Menu:** Sobre Nós → `en` ou `pt`

| Campo | Função |
|-------|--------|
| **Headline** | Título da página (rich text) |
| **Texto introdutório** | Bloco de texto ao lado do título (layout desktop) |
| **Seções do acordeão** | Título, texto e imagem lateral por seção |

Hero: edite em **Heroes**. A página também exibe **Últimos projetos** — os projetos vêm do CPT **Projects**, não desta tela.

---

### 7. Capacidades

**Menu:** Capacidades → `en` ou `pt`

| Campo | Função |
|-------|--------|
| **Headline** | Título no topo |
| **Seções do acordeão** | Título, texto e imagem por seção |

Sem hero de imagem — só headline + acordeão.

---

### 8. Educação

**Menu:** Educação → `en` ou `pt`

| Campo | Função |
|-------|--------|
| **Headline** | Título abaixo do hero |
| **Seções do acordeão** | Título e texto (sem imagem lateral) |

Hero: **Heroes**. A grade de projetos na página vem de projetos na categoria **education** em **Projects**.

---

### 9. Contato

**Menu:** Contato → `en` ou `pt`

| Campo | Função |
|-------|--------|
| **Headline** | Título da página |
| **Blocos** | Colunas de informação (título + conteúdo cada) |

Hero: **Heroes**.

---

### 10. Página de projetos

**Menu:** Página de projetos → `en` ou `pt`

| Campo | Função |
|-------|--------|
| **Título** | Título da página `/projects` |
| **Headline** | Subtítulo / destaque |
| **Mensagem vazia** | Texto quando não há projetos |

Os projetos em si são geridos em **Projects**.

---

### 11. Projects (projetos)

**Menu:** Projects → abra um projeto

Cada projeto tem **slug próprio** (não é `en`/`pt`). Para versão em português, use a coluna **Language → PT** na lista (vincula tradução).

**Caixa “Conteúdo do Projeto (site)”:**

| Campo | Função |
|-------|--------|
| **Imagem de fundo (hero)** | Topo da página — 1:1 no mobile, 16:9 no desktop |
| **Logo** | Sobre a imagem no desktop (canto inferior esquerdo); no mobile fica oculta |
| **Acordeão** | Seções com **título editável** + texto (adicione/remova seções) |
| **Galeria** | Fotos abaixo do conteúdo — use **+ Adicionar imagem** (quantas quiser) |

**Barra lateral:**

- **Resumo** — texto à esquerda na página do projeto
- **Imagem destacada** — só usada como fallback se o campo Logo estiver vazio
- **Categorias** — ex.: Education, Motion Design (filtram listagens)

> O editor principal do WordPress foi desativado para projetos. Use apenas a caixa **Conteúdo do Projeto (site)** e o **Resumo** na barra lateral.

---

### 12. Marcas

**Menu:** Marcas

Cadastre cada marca com **título** e **imagem destacada** (logo). A ordem pode ser ajustada pelo campo de ordem (atributos de página), se disponível.

---

### 13. Footer

**Menu:** Footer → `en` ou `pt`

| Grupo | Campos |
|-------|--------|
| Marca grande | Texto tipo “REGULARSWITCH” |
| Colunas 1–3 | Título, subtítulo, link (Contact, Newsletter, Join Us…) |
| Legal | Marca, textos e links de Privacidade e Cookies |

---

## Fluxo recomendado para uma alteração

```
1. Faça login no staging (ou local)
2. Edite o post / campo desejado
3. Clique em Atualizar ou Publicar
4. Abra o site Next.js (preview ou local) e confira EN e PT
5. Se estiver ok, repita a mesma edição em produção
```

### Conferir tradução PT

1. Salve a versão **EN**
2. Na lista, coluna **Language**, clique **PT**
3. Preencha os campos em português
4. Salve
5. Visite `/PT/...` no site

---

## Mídia (imagens)

- **Selecionar imagem** abre a biblioteca do WordPress
- **Enviar** novas imagens em Mídia → Adicionar
- Prefira JPG/WebP otimizados; heroes panorâmicos funcionam melhor em proporção larga

---

## Atualizar o WordPress (core e plugins)

Para atualizar a versão do WordPress, plugins ou tema no servidor:

1. **Backup** (All-in-One WP Migration → Exportar)
2. **Atualizações** no menu lateral
3. Atualize **core** → **plugins** → **tema**
4. Teste REST API e páginas do site

Detalhes de staging: [wordpress-staging.md](./wordpress-staging.md).

> Atualizar o WordPress **não** envia automaticamente o plugin **Tradução** do repositório de código. Mudanças no plugin exigem deploy manual do ZIP (`./scripts/wp-package-plugins.sh`).

---

## O que evitar

| Não faça | Motivo |
|----------|--------|
| Editar produção sem testar no staging | Risco de quebrar o site ao vivo |
| Colar HTML do front no editor | Duplica markup e quebra layout |
| Criar posts `en`/`pt` duplicados manualmente | Use sempre a coluna **Language** |
| Mudar slug de páginas bilíngues para outro valor | O sistema espera `en` ou `pt` |
| Confiar no item **Tradutor** (legado) | Fluxo atual usa posts EN/PT e coluna Language |

---

## Problemas comuns

**Salvei mas não aparece no site**

- Confirme que salvou o post **Publicado** (não Rascunho)
- Confirme o idioma (`en` vs `pt`)
- No staging, o preview Vercel precisa apontar `API` para o WP correto
- Limpe cache (LiteSpeed Cache → Purge All), se instalado

**Seções do acordeão não salvaram**

- Clique em **Atualizar** depois de editar todas as seções
- Não feche a aba antes do salvamento terminar

**Hero não mudou**

- Imagens de Sobre/Educação/Contato vêm de **Heroes**, não do post da página

**Footer vazio no site**

- Crie/edite posts **Footer** com slugs `en` e `pt` e preencha os campos

**Menu do header desatualizado**

- Edite em **Aparência → Menus**, não em Interface do site

---

## Referência rápida — URL pública ↔ menu WP

| Página no site | Rota Next.js | Menu WP |
|----------------|--------------|---------|
| Home | `/` e `/PT` | Intro, Visual da home |
| Projetos | `/projects` | Página de projetos + Projects |
| Capacidades | `/capabilities` | Capacidades |
| Educação | `/education` | Educação + Heroes |
| Sobre | `/about-us` | Sobre Nós + Heroes |
| Contato | `/contact` | Contato + Heroes |
| Projeto individual | `/project/{slug}` | Projects |

---

## Mais documentação

- Deploy e staging: [wordpress-staging.md](./wordpress-staging.md)
- Plugins versionados: [wordpress/README.md](../wordpress/README.md)
