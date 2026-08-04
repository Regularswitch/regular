import type { SiteUiContent, SiteUiLabels, SiteUiLayout, SiteUiLocale, SiteUiNavLink } from '../../types';
import {
	ABOUT_PAGE_SLUG,
	CAPABILITIES_PAGE_SLUG,
	CONTACT_PAGE_SLUG,
	EDUCATION_PAGE_SLUG,
	PROJECTS_PAGE_SLUG,
	pagePath,
} from './pageSlugs';

const NAV_EN: SiteUiNavLink[] = [
	{ label: 'Projects', href: pagePath(PROJECTS_PAGE_SLUG) },
	{ label: 'Capabilities', href: pagePath(CAPABILITIES_PAGE_SLUG) },
	{ label: 'Education', href: pagePath(EDUCATION_PAGE_SLUG) },
	{ label: 'About', href: pagePath(ABOUT_PAGE_SLUG) },
	{ label: 'Contact', href: pagePath(CONTACT_PAGE_SLUG) },
];

const NAV_PT: SiteUiNavLink[] = [
	{ label: 'Projetos', href: pagePath(PROJECTS_PAGE_SLUG) },
	{ label: 'Capacidades', href: pagePath(CAPABILITIES_PAGE_SLUG) },
	{ label: 'Educação', href: pagePath(EDUCATION_PAGE_SLUG) },
	{ label: 'Sobre Nós', href: pagePath(ABOUT_PAGE_SLUG) },
	{ label: 'Contato', href: pagePath(CONTACT_PAGE_SLUG) },
];

const LABELS_EN: SiteUiLabels = {
	selectedProjects: 'Selected Projects',
	latestProjects: 'The Latest',
	brandsMarquee: 'Brands that trust us',
	seeMoreProjects: 'See more projects',
	seeMoreWork: 'See more work',
	whatsNewLabel: "What's New",
	whatsNewTitle: 'Regular Switch',
	whatsNewSubtitle: 'New website',
};

const LABELS_PT: SiteUiLabels = {
	selectedProjects: 'Projetos Selecionados',
	latestProjects: 'Últimos',
	brandsMarquee: 'Marcas que confiam em nós',
	seeMoreProjects: 'Veja mais projetos',
	seeMoreWork: 'Veja mais projetos',
	whatsNewLabel: 'Novidades',
	whatsNewTitle: 'Regular Switch',
	whatsNewSubtitle: 'Novo site',
};

export const DEFAULT_SITE_UI_LAYOUT: SiteUiLayout = {
	homeColumns: 2,
	projectsInitialCount: 5,
	latestCount: 6,
};

export const DEFAULT_SITE_UI: SiteUiContent = {
	en: { labels: LABELS_EN, nav: NAV_EN },
	pt: { labels: LABELS_PT, nav: NAV_PT },
	layout: DEFAULT_SITE_UI_LAYOUT,
};

export function getDefaultSiteUiContent(): SiteUiContent {
	return {
		...DEFAULT_SITE_UI,
		layout: { ...DEFAULT_SITE_UI_LAYOUT },
	};
}

export function normalizeSiteUiLayout(raw?: Partial<SiteUiLayout> | null): SiteUiLayout {
	const homeColumns = Number(raw?.homeColumns);
	const projectsInitialCount = Number(raw?.projectsInitialCount);
	const latestCount = Number(raw?.latestCount);

	return {
		homeColumns: homeColumns === 1 || homeColumns === 3 ? homeColumns : 2,
		projectsInitialCount:
			Number.isFinite(projectsInitialCount) && projectsInitialCount > 0
				? Math.min(100, Math.floor(projectsInitialCount))
				: DEFAULT_SITE_UI_LAYOUT.projectsInitialCount,
		latestCount:
			Number.isFinite(latestCount) && latestCount >= 3 && latestCount <= 12
				? Math.floor(latestCount)
				: DEFAULT_SITE_UI_LAYOUT.latestCount,
	};
}
