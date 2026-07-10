import type { CapabilitySection, CapabilitiesContent } from '../types';

export type { CapabilitySection, CapabilitiesContent };

const SERVICES_TITLE_PT = 'Como podemos ajudar:';
const SERVICES_TITLE_EN = 'How we can help:';

const CAPABILITIES_PT: CapabilitySection[] = [
	{
		title: 'ESTRATÉGIA & NARRATIVA',
		lead: 'Como traduzir propósito, território e visão em narrativas visuais claras e consistentes?',
		body: '<p>Desenvolvemos posicionamentos, arquiteturas de marca e narrativas que orientam decisões criativas em diferentes contextos — do branding à comunicação institucional.</p>',
		servicesTitle: SERVICES_TITLE_PT,
		services: [
			'Posicionamento de marca',
			'Arquitetura de marca',
			'Narrativa visual',
			'Tom de voz',
			'Estratégia criativa',
			'Pesquisa e diagnóstico',
		],
		imageProjectSlug: 'central-1926',
	},
	{
		title: 'BRANDING & SISTEMAS VISUAIS',
		lead: 'Como construir identidades visuais capazes de evoluir de forma consistente entre diferentes contextos, plataformas e experiências?',
		body: '<p>Projetamos sistemas visuais flexíveis e duradouros — da concepção da identidade à sua aplicação em múltiplos pontos de contato, garantindo coerência sem perder vitalidade.</p>',
		servicesTitle: SERVICES_TITLE_PT,
		services: [
			'Identidade visual',
			'Sistemas gráficos',
			'Direção de arte',
			'Arquitetura visual',
			'Design de logotipo',
			'Guidelines de marca',
			'Linguagem visual',
			'Aplicações de marca',
			'Design tipográfico',
			'Universos visuais de campanha',
		],
		imageProjectSlug: 'frentistas-do-brasil',
	},
	{
		title: 'EXPERIÊNCIAS DIGITAIS',
		lead: 'Como criar interfaces e ecossistemas digitais que ampliam a presença da marca no contemporâneo?',
		body: '<p>Concebemos sites, plataformas e experiências digitais com foco em clareza, performance e identidade — integrando design, conteúdo e interação.</p>',
		servicesTitle: SERVICES_TITLE_PT,
		services: [
			'Design de interfaces',
			'Websites e landing pages',
			'Design systems digitais',
			'UX/UI',
			'Prototipação',
			'Direção de arte digital',
		],
		imageProjectSlug: 'piktiz',
	},
	{
		title: 'CONTEÚDO & CAMPANHAS',
		lead: 'Como transformar estratégia em linguagem visual para campanhas e comunicação contínua?',
		body: '<p>Criamos universos gráficos para campanhas, lançamentos e conteúdos editoriais — conectando marca, mensagem e formato em peças de alto impacto.</p>',
		servicesTitle: SERVICES_TITLE_PT,
		services: [
			'Direção criativa de campanha',
			'Key visuals',
			'Conteúdo para redes',
			'Peças institucionais',
			'Editorial de marca',
			'Adaptações multiformato',
		],
		imageProjectSlug: 'club-athletico-paulistano',
	},
	{
		title: 'MOTION, 3D & EXPERIÊNCIAS VISUAIS',
		lead: 'Como expandir a identidade para movimento, tridimensionalidade e ambientes imersivos?',
		body: '<p>Exploramos motion design, 3D e recursos visuais avançados para ampliar narrativas de marca em vídeo, digital e espaços experienciais.</p>',
		servicesTitle: SERVICES_TITLE_PT,
		services: [
			'Motion design',
			'Animação de marca',
			'3D e renders',
			'Vinhetas e aberturas',
			'Conteúdo em vídeo',
			'Experiências visuais',
		],
		imageProjectSlug: 'workshop-explore-france-2026',
	},
	{
		title: 'DESIGN EDITORIAL & CULTURAL',
		lead: 'Como dar forma a publicações, exposições e projetos culturais com rigor gráfico e sensibilidade contemporânea?',
		body: '<p>Atuamos em livros, catálogos, materiais expositivos e projetos editoriais — unindo pesquisa visual, tipografia e direção de arte.</p>',
		servicesTitle: SERVICES_TITLE_PT,
		services: [
			'Design editorial',
			'Catálogos e publicações',
			'Design expositivo',
			'Projetos culturais',
			'Direção de arte editorial',
			'Sistemas para coleções',
		],
		imageProjectSlug: 'exposicao-sonhos',
	},
];

const CAPABILITIES_EN: CapabilitySection[] = [
	{
		title: 'STRATEGY & NARRATIVE',
		lead: 'How do we translate purpose, territory and vision into clear, consistent visual narratives?',
		body: '<p>We develop positioning, brand architectures and narratives that guide creative decisions across contexts — from branding to institutional communication.</p>',
		servicesTitle: SERVICES_TITLE_EN,
		services: [
			'Brand positioning',
			'Brand architecture',
			'Visual narrative',
			'Tone of voice',
			'Creative strategy',
			'Research and diagnosis',
		],
		imageProjectSlug: 'central-1926',
	},
	{
		title: 'BRANDING & VISUAL SYSTEMS',
		lead: 'How do we build visual identities that evolve consistently across contexts, platforms and experiences?',
		body: '<p>We design flexible, enduring visual systems — from identity conception to application across touchpoints, ensuring coherence without losing vitality.</p>',
		servicesTitle: SERVICES_TITLE_EN,
		services: [
			'Visual identity',
			'Graphic systems',
			'Art direction',
			'Visual architecture',
			'Logo design',
			'Brand guidelines',
			'Visual language',
			'Brand applications',
			'Typographic design',
			'Campaign visual worlds',
		],
		imageProjectSlug: 'frentistas-do-brasil',
	},
	{
		title: 'DIGITAL EXPERIENCES',
		lead: 'How do we create interfaces and digital ecosystems that expand brand presence in the contemporary landscape?',
		body: '<p>We conceive websites, platforms and digital experiences focused on clarity, performance and identity — integrating design, content and interaction.</p>',
		servicesTitle: SERVICES_TITLE_EN,
		services: [
			'Interface design',
			'Websites and landing pages',
			'Digital design systems',
			'UX/UI',
			'Prototyping',
			'Digital art direction',
		],
		imageProjectSlug: 'piktiz',
	},
	{
		title: 'CONTENT & CAMPAIGNS',
		lead: 'How do we turn strategy into visual language for campaigns and ongoing communication?',
		body: '<p>We create graphic worlds for campaigns, launches and editorial content — connecting brand, message and format in high-impact pieces.</p>',
		servicesTitle: SERVICES_TITLE_EN,
		services: [
			'Campaign creative direction',
			'Key visuals',
			'Social content',
			'Institutional pieces',
			'Brand editorial',
			'Multi-format adaptations',
		],
		imageProjectSlug: 'club-athletico-paulistano',
	},
	{
		title: 'MOTION, 3D & VISUAL EXPERIENCES',
		lead: 'How do we extend identity into motion, three-dimensionality and immersive environments?',
		body: '<p>We explore motion design, 3D and advanced visual resources to expand brand narratives in video, digital and experiential spaces.</p>',
		servicesTitle: SERVICES_TITLE_EN,
		services: [
			'Motion design',
			'Brand animation',
			'3D and renders',
			'Bumpers and openings',
			'Video content',
			'Visual experiences',
		],
		imageProjectSlug: 'workshop-explore-france-2026',
	},
	{
		title: 'EDITORIAL & CULTURAL DESIGN',
		lead: 'How do we shape publications, exhibitions and cultural projects with graphic rigor and contemporary sensitivity?',
		body: '<p>We work on books, catalogues, exhibition materials and editorial projects — combining visual research, typography and art direction.</p>',
		servicesTitle: SERVICES_TITLE_EN,
		services: [
			'Editorial design',
			'Catalogues and publications',
			'Exhibition design',
			'Cultural projects',
			'Editorial art direction',
			'Collection systems',
		],
		imageProjectSlug: 'exposicao-sonhos',
	},
];

export const DEFAULT_CAPABILITIES_PT: CapabilitiesContent = {
	headline:
		'Atuamos na interseção de <strong>estratégia</strong>, <strong>criatividade</strong> e <strong>execução</strong> — desenvolvendo <strong>identidades</strong>, <strong>experiências digitais</strong> e <strong>narrativas visuais</strong> para marcas e instituições que buscam <strong>relevância contemporânea</strong>.',
	sections: CAPABILITIES_PT,
};

export const DEFAULT_CAPABILITIES_EN: CapabilitiesContent = {
	headline:
		'We operate at the intersection of <strong>strategy</strong>, <strong>creativity</strong> and <strong>execution</strong> — developing <strong>identities</strong>, <strong>digital experiences</strong> and <strong>visual narratives</strong> for brands and institutions seeking <strong>contemporary relevance</strong>.',
	sections: CAPABILITIES_EN,
};

export function getDefaultCapabilitiesContent(locale: 'en' | 'pt'): CapabilitiesContent {
	return locale === 'pt' ? DEFAULT_CAPABILITIES_PT : DEFAULT_CAPABILITIES_EN;
}
