import type { SiteUiContent, SiteUiLabels, SiteUiLocale, SiteUiNavLink } from '../types';
import {
	ABOUT_PAGE_SLUG,
	CAPABILITIES_PAGE_SLUG,
	CONTACT_PAGE_SLUG,
	EDUCATION_PAGE_SLUG,
	pagePath,
	WORK_PAGE_SLUG,
} from './pageSlugs';

const NAV_EN: SiteUiNavLink[] = [
	{ label: 'Projects', href: pagePath(WORK_PAGE_SLUG) },
	{ label: 'Capabilities', href: pagePath(CAPABILITIES_PAGE_SLUG) },
	{ label: 'Education', href: pagePath(EDUCATION_PAGE_SLUG) },
	{ label: 'About', href: pagePath(ABOUT_PAGE_SLUG) },
	{ label: 'Contact', href: pagePath(CONTACT_PAGE_SLUG) },
];

const NAV_PT: SiteUiNavLink[] = [
	{ label: 'Projetos', href: pagePath(WORK_PAGE_SLUG) },
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

export const DEFAULT_SITE_UI: SiteUiContent = {
	en: { labels: LABELS_EN, nav: NAV_EN },
	pt: { labels: LABELS_PT, nav: NAV_PT },
};

export function getDefaultSiteUiContent(): SiteUiContent {
	return DEFAULT_SITE_UI;
}
