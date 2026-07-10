import { GetApi } from '../components/ApiWp';
import {
	DEFAULT_ABOUT_HERO_IMAGE,
	getDefaultAboutContent,
	type AboutAccordionSection,
	type AboutContent,
} from './aboutDefaults';
import { fetchWpPageByLocale } from './fetchWpPageByLocale';
import { ABOUT_WP_BASE_SLUG } from './pageSlugs';
import {
	extractHeroImageFromHtml,
	mergeAboutAccordionSections,
	parsePageBodyAfterHeadline,
	parsePageHeadline,
	parsePageAccordionFromHeadings,
} from './parsePageContent';
import { sortProjectsByDate } from './sortProjects';
import type { Projects } from '../types';

export type AboutPageData = {
	content: AboutContent;
	latestProjects: Projects;
};

function attachSectionImages(sections: AboutAccordionSection[], projects: Projects): AboutAccordionSection[] {
	return sections.map((section) => {
		if (section.image || !section.imageProjectSlug) return section;

		const image = projects.find((project) => project.slug === section.imageProjectSlug)?.image_full;
		return image ? { ...section, image } : section;
	});
}

function buildAboutContent(
	pageContent: string | undefined,
	pageImage: string | undefined,
	locale: 'en' | 'pt',
	projects: Projects,
): AboutContent {
	const defaults = getDefaultAboutContent(locale);
	const html = pageContent ?? '';

	const heroImage =
		pageImage || extractHeroImageFromHtml(html) || defaults.heroImage || DEFAULT_ABOUT_HERO_IMAGE;

	const headline = parsePageHeadline(html) ?? defaults.headline;
	const body = parsePageBodyAfterHeadline(html) ?? defaults.body;
	const parsedAccordion = parsePageAccordionFromHeadings(html, ['h3']);

	return {
		heroImage,
		headline,
		body,
		accordionSections: attachSectionImages(
			mergeAboutAccordionSections(parsedAccordion, defaults.accordionSections),
			projects,
		),
	};
}

async function fetchAboutFromWp(locale: 'en' | 'pt') {
	const query: Record<string, string | number> = { _embed: '' };

	const [page, projects] = await Promise.all([
		fetchWpPageByLocale(ABOUT_WP_BASE_SLUG, locale, query),
		GetApi('/project/', { per_page: 100, ...query }),
	]);

	return {
		content: buildAboutContent(page?.content, page?.image_full, locale, projects),
		latestProjects: sortProjectsByDate(projects),
	};
}

export async function fetchAboutPage(locale: 'en' | 'pt'): Promise<AboutPageData> {
	return fetchAboutFromWp(locale).catch((error) => {
		console.error('Error fetching about page', error);
		return {
			content: buildAboutContent(undefined, undefined, locale, []),
			latestProjects: [],
		};
	});
}
