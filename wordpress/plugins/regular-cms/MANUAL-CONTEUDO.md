# Manual de conteúdo — Regular Switch (CMS)

Guia prático para o time de conteúdo publicar e atualizar o site no **WordPress (Regular CMS)**.

O site público é o Next.js; o que você edita no admin **alimenta** as páginas.  
Após **Publicar / Atualizar**, as mudanças aparecem no site (pode haver alguns minutos de cache em staging/produção).

> Documentação técnica do admin (devs): [ADMIN.md](./ADMIN.md)

---

## 1. Acesso e menu

1. Entre no WordPress com seu usuário.
2. No menu lateral você verá principalmente:
   - **Conteúdo** — páginas e projetos do site
   - **Sistema** — rodapé, cookies, visual da home, labels (use com cuidado)
   - **Mídia** — biblioteca de imagens e vídeos

Na home do painel há atalhos para as seções principais.

### Onde editar o quê (visão rápida)

| Quero mudar… | No admin vá em… | Aparece no site em… |
|--------------|-----------------|---------------------|
| Texto grande da home | **Conteúdo → Intro** | `/` e `/PT/` |
| Um case / projeto | **Conteúdo → Projetos** | `/project/…` e `/PT/project/…` |
| Tags / arquivos de categoria | **Projetos → Categorias** | `/category/…` e `/PT/category/…` |
| Listagem “Projects / Work” | **Conteúdo → Página de projetos** | `/projects` e `/PT/projects` |
| Sobre Nós | **Conteúdo → Sobre Nós** (About) | `/about-us` e `/PT/about-us` |
| Capacidades + FAQ | **Conteúdo → Capacidades** | `/capabilities` e `/PT/capabilities` |
| Educação | **Conteúdo → Educação** | `/education` e `/PT/education` |
| Contato | **Conteúdo → Contato** | `/contact` e `/PT/contact` |
| Logos das marcas (home) | **Conteúdo → Marcas** | faixa de marcas na home |
| Rodapé | **Sistema → Footer** | todas as páginas |
| Privacidade / cookies | **Sistema → Privacidade & Cookies** | `/privacy-policy`, `/cookies-policy` (+ PT) |
| Title / descrição Google | Metabox **SEO** na própria página/projeto | abas do browser + busca |

---

## 2. Conceitos que o time precisa saber

### Um post, dois idiomas (EN e PT)

Na maioria das seções existe **um único post**.  
Dentro dele há abas **English** e **Português** — preencha os dois.

- O visitante vê o idioma conforme a URL (`/` = EN, `/PT/` = PT).
- **Não** crie um segundo post “só de PT” para Intro, About, Capacidades, etc.

**Exceção — categorias:** cada categoria pode ter um termo EN e um vínculo PT (gêmeo). Preencha SEO/H1/intro nos dois quando existirem.

**Marcas:** não têm tradução; o mesmo logo/nome vale para EN e PT.

### Publicar

- Use sempre o botão **Publicar** ou **Atualizar** na coluna da direita.
- Se não clicar em Atualizar, a alteração **não** vai para o site.
- Rascunho (`Draft`) não aparece no site público.

### Textos com negrito

Nos campos com editor (barra B / I / link):

- Use o botão **B** para destacar palavras.
- Evite colar de Word/Google Docs com formatação estranha; se o texto “quebrar”, cole como texto simples e reaplique o negrito.

### Imagens e vídeos

1. Prefira enviar pela **Mídia** do WordPress (ou pelo seletor/dropzone do campo).
2. Preencha o **texto alternativo (alt)** na biblioteca — ajuda SEO e acessibilidade.
3. Em projetos, a **imagem destacada** (barra lateral) costuma ser o card da home/listagem; hero e galeria ficam na aba **Mídia** do projeto.

### SEO (title e meta description)

Quase toda página de conteúdo tem o bloco **SEO**:

- Aba **English** e **Português**
- **Title tag** (~ até 60 caracteres)
- **Meta description** (~ até 160 caracteres)
- Há uma **prévia** no estilo Google — use para revisar

Se deixar vazio, o site usa um fallback genérico (pior para busca).

---

## 3. Fluxos do dia a dia

### A) Atualizar a home (headline)

1. **Conteúdo → Intro** (abra o post único).
2. Aba **English**: título grande + parágrafo.
3. Aba **Português**: equivalentes.
4. Preencha o metabox **SEO** (home).
5. **Atualizar**.

Cores/animação do blob da home: **Sistema → Visual da home** (só se o time de design pedir).

### B) Criar um projeto novo

1. **Conteúdo → Projetos → Adicionar**.
2. **Título** (EN) no topo — vira base do nome e do slug.
3. Aba **Geral**
   - Ajuste o **slug** se precisar (URL: `/project/seu-slug`).
   - **Destaque na home:** marque só se este for *o* projeto em destaque (só pode haver **um**; ao salvar, os outros saem).
   - **Vignette:** exibir ou não o logo no canto do card.
4. Aba **English**
   - Resumo (texto da coluna esquerda).
   - Seções do acordeão (ex.: Contexto, Solução…).
   - Vídeos YouTube (se houver).
5. Aba **Português**
   - Título PT (opcional; se vazio, o site pode usar o EN).
   - Resumo, acordeão e YouTube em PT.
6. Aba **Mídia** (compartilhada EN/PT)
   - Hero / fundo da página do projeto.
   - Logo (vignette).
   - Galeria: adicione várias mídias, arraste para ordenar; estrela = largura total no layout.
7. Barra lateral: **Imagem destacada** (card na listagem/home) + **Categorias**.
8. Metabox **SEO** (EN e PT).
9. **Publicar**.

Conferir no site: `/project/seu-slug` e `/PT/project/seu-slug`.

### C) Editar um projeto existente

1. **Conteúdo → Projetos** → clique no nome.
2. Altere só o que precisa nas abas.
3. **Atualizar**.

### D) Categorias (tags de projeto)

1. **Conteúdo → Projetos → Categorias**.
2. Crie ou edite o termo.
3. Preencha:
   - **H1 do arquivo** (título da página da categoria)
   - **Introdução** (parágrafo)
   - **SEO title** e **meta description**
4. Associe a categoria aos projetos na edição de cada projeto.
5. Página pública: `/category/slug-da-categoria` (e `/PT/category/…`).

> A categoria especial usada na grade “Selected” da home pode ter slug `home` — **não** trata como página de arquivo normal; alinhe com o time se for mexer nela.

### E) Página de listagem de projetos

1. **Conteúdo → Página de projetos**.
2. Textos EN/PT (headline, mensagens).
3. SEO EN/PT.
4. A grade de cards vem dos **projetos publicados**, não deste post.

### F) Capacidades (serviços + FAQ)

1. **Conteúdo → Capacidades**.
2. Em cada idioma: headline, seções (com imagem/texto/links), bloco **FAQ**.
3. FAQ bem preenchido melhora busca e aparece estruturado no site.
4. SEO + **Atualizar**.

### G) Sobre / Educação / Contato

Mesmo padrão:

1. Abra o post único no menu **Conteúdo**.
2. Hero/mídia compartilhada (quando existir) vale para EN e PT.
3. Textos e seções nas abas de idioma.
4. SEO → **Atualizar**.

### H) Marcas (faixa da home)

1. **Conteúdo → Marcas**.
2. Cada marca = um item (nome + imagem destacada/logo).
3. Sem abas EN/PT.
4. Ordem: use a ordenação disponível na listagem (atributos de página / ordem do menu, conforme configurado no admin).

### I) Rodapé e legal

- **Footer:** textos e links por idioma; redes sociais em geral **iguais** nos dois idiomas.
- **Privacidade & Cookies:** um post alimenta as páginas de privacidade e cookies; edite EN e PT com cuidado (texto jurídico).

---

## 4. Checklist antes de publicar

- [ ] Versão **English** e **Português** preenchidas (quando houver abas)
- [ ] Links e slugs conferidos (sem espaços, sem acento no slug)
- [ ] Imagens com **alt text**
- [ ] Projeto: imagem destacada + hero/galeria se a página do case precisar
- [ ] Categorias corretas no projeto
- [ ] SEO title e description nos dois idiomas
- [ ] Clicou em **Publicar / Atualizar**
- [ ] Abriu o site no idioma certo e deu um refresh forçado se necessário

---

## 5. O que evitar

| Evitar | Por quê |
|--------|---------|
| Criar vários posts “Intro PT”, “Intro EN” | O modelo certo é **um** post com abas |
| Marcar vários projetos como “destaque home” | Só um vale; o último salvo ganha |
| Apagar categorias em uso sem realocar projetos | Quebra arquivos e filtros |
| Colar HTML cru no lugar do editor | Quebra layout tipográfico |
| Mexer em **Sistema → Interface do site / Design System** sem alinhamento | Afeta labels globais e ferramentas de produto |
| Editar plugins ou código | Fora do escopo de conteúdo |

---

## 6. Problemas comuns

**“Salvei e não apareceu no site”**  
→ Confirmou **Atualizar**? Está **Publicado** (não rascunho)? Espere o cache ou peça ao time técnico um purge.

**“Só mudou em inglês”**  
→ Preencha a aba **Português** e salve de novo. Abra a URL com `/PT/`.

**“A imagem do card está errada, mas o hero está certo”**  
→ Card = **Imagem destacada** (lateral). Página do projeto = aba **Mídia**.

**“A URL do projeto ficou estranha”**  
→ Ajuste o **slug** na aba Geral e atualize. Avise o time se o slug antigo já estava indexado no Google.

**“Não acho Categorias”**  
→ **Conteúdo → Projetos → Categorias** (submenu de Projetos).

---

## 7. Contatos / suporte interno

- Dúvidas de texto, tom e SEO editorial: time de conteúdo / marketing  
- Bugs do admin, deploy, cache, permissões: time de produto / engenharia  
- Referência técnica do painel: [ADMIN.md](./ADMIN.md)

---

*Última atualização alinhada ao Regular CMS com admin Conteúdo/Sistema e posts bilíngues por abas.*
