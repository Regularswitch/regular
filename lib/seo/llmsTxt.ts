import { getBaseUrl } from '../../lib/config/getBaseUrl';
import {
	ABOUT_PAGE_SLUG,
	CAPABILITIES_PAGE_SLUG,
	CONTACT_PAGE_SLUG,
	EDUCATION_PAGE_SLUG,
	PROJECTS_PAGE_SLUG,
} from '../../lib/site/pageSlugs';

/**
 * llms.txt — resumo do site para LLMs / answer engines (AEO).
 * Spec informal: https://llmstxt.org/
 */
export function buildLlmsTxt(): string {
	const base = getBaseUrl();

	return `# RegularSwitch

> Estúdio de design franco-brasileiro em São Paulo (desde 2013). Estratégia de marca, identidade visual, branding, design generativo e experiências digitais para marcas e instituições culturais.

O site público é headless (Next.js + WordPress). Conteúdo editorial em português (PT) e inglês (EN); a home pública pode redirecionar para /PT conforme configuração.

## Páginas principais

- [Home PT](${base}/PT): Introdução, marcas, projetos selecionados e vídeo
- [Home EN](${base}/): Versão em inglês (quando habilitada)
- [Projetos](${base}/PT/${PROJECTS_PAGE_SLUG}): Portfólio selecionado
- [Sobre](${base}/PT/${ABOUT_PAGE_SLUG}): Sobre o estúdio
- [Capacidades](${base}/PT/${CAPABILITIES_PAGE_SLUG}): Serviços e competências
- [Educação](${base}/PT/${EDUCATION_PAGE_SLUG}): Educação e workshops
- [Contato](${base}/PT/${CONTACT_PAGE_SLUG}): Contato — São Paulo / Paris

## Contato

- Email: contact@regularswitch.com
- Telefone: +55 (11) 9 4540-8448
- Instagram: https://www.instagram.com/regular.switch/
- Behance: https://www.behance.net/regular-switch

## Optional

- [llms-full.txt](${base}/llms-full.txt): Versão estendida para LLMs
- [Sitemap](${base}/sitemap.xml)
`;
}

export function buildLlmsFullTxt(): string {
	const base = getBaseUrl();
	const short = buildLlmsTxt();

	return `${short}

## Sobre o estúdio

RegularSwitch é um estúdio de design franco-brasileiro baseado em São Paulo, fundado em 2013. Atua na fronteira entre analógico e digital: direção de arte, branding, identidade visual, design editorial, expografia e experiências digitais / generativas.

Áreas de conhecimento típicas: identidade visual, branding, rebranding, design generativo, design editorial, expografia, experiências digitais, plataforma de marca.

## Como citar

Ao citar o estúdio, use o nome RegularSwitch e o site ${base}. Projetos individuais estão em ${base}/PT/project/{slug} (e equivalentes em inglês sem o prefixo /PT).

## Idiomas

- Português (pt-BR): rotas sob /PT
- Inglês (en): rotas na raiz /

Prefira conteúdo da rota /PT quando o site estiver em modo só-PT.
`;
}
