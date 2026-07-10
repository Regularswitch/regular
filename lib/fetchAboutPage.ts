import { GetApi } from '../components/ApiWp';
import {
	DEFAULT_ABOUT_HERO_IMAGE,
	getDefaultAboutContent,
	type AboutAccordionSection,
	type AboutContent,
} from './aboutDefaults';
import {
	extractHeroImageFromHtml,
	mergeAboutAccordionSections,
	parsePageBodyAfterHeadline,
	parsePageHeadline,
	parsePageAccordionFromHeadings,
} from './parsePageContent';
import { getBaseUrl } from './getBaseUrl';
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

async function fetchAboutFromWp(translate?: string) {
	const query: Record<string, string | number> = { _embed: '' };
	if (translate) query.translate = translate;

	const [pages, projects] = await Promise.all([
		GetApi('/pages', { slug: 'about', ...query }),
		GetApi('/project/', { per_page: 100, ...query }),
	]);

	const page = pages[0];
	const locale = translate === 'PT' ? 'pt' : 'en';

	return {
		content: buildAboutContent(page?.content, page?.image_full, locale, projects),
		latestProjects: sortProjectsByDate(projects),
	};
}

export async function fetchAboutPage(locale: 'en' | 'pt'): Promise<AboutPageData> {
	if (locale === 'en') {
		return fetchAboutFromWp().catch((error) => {
			console.error('Error fetching about page', error);
			return {
				content: buildAboutContent(undefined, undefined, 'en', []),
				latestProjects: [],
			};
		});
	}

	const base = getBaseUrl();
	const headers = { Cookie: 'language=PT' };

	try {
		const [pages, projects] = await Promise.all([
			fetch(`${base}/api/about`, { headers }).then((r) => r.json() as Promise<Projects>),
			fetch(`${base}/api/project`, { headers }).then((r) => r.json() as Promise<Projects>),
		]);

		const page = pages[0];

		return {
			content: buildAboutContent(page?.content, page?.image_full, 'pt', projects),
			latestProjects: sortProjectsByDate(projects),
		};
	} catch (error) {
		console.error('Error fetching PT about page', error);
		return {
			content: buildAboutContent(undefined, undefined, 'pt', []),
			latestProjects: [],
		};
	}
}
