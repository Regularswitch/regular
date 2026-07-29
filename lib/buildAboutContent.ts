import {
	DEFAULT_ABOUT_HERO_IMAGE,
	getDefaultAboutContent,
	type AboutAccordionSection,
	type AboutContent,
} from './aboutDefaults';
import { sanitizeAboutBody, sanitizeAboutHeadline } from './sanitizeWpRichText';
import { wpMediaUrl } from './wpMediaUrl';
import type { Projects } from '../types';

function attachSectionImages(sections: AboutAccordionSection[], projects: Projects): AboutAccordionSection[] {
	return sections.map((section) => {
		if (section.image || !section.imageProjectSlug) return section;

		const image = projects.find((project) => project.slug === section.imageProjectSlug)?.image_full;
		return image ? { ...section, image } : section;
	});
}

function normalizeWpSections(sections: AboutAccordionSection[] | undefined): AboutAccordionSection[] {
	if (!sections?.length) return [];

	return sections
		.filter((section) => section.title?.trim())
		.map((section) => ({
			title: section.title,
			body: section.body ?? '',
			image: section.image ? (wpMediaUrl(section.image) ?? section.image) : undefined,
		}));
}

export function buildAboutContent(
	wp: AboutContent | null | undefined,
	locale: 'en' | 'pt',
	projects: Projects,
): AboutContent {
	const defaults = getDefaultAboutContent(locale);
	const defaultSections = attachSectionImages(defaults.accordionSections, projects);

	if (!wp) {
		return {
			...defaults,
			heroImage: defaults.heroImage ?? DEFAULT_ABOUT_HERO_IMAGE,
			accordionSections: defaultSections,
		};
	}

	const hasWpContent =
		Boolean(wp.heroImage) ||
		Boolean(wp.heroVideo) ||
		Boolean(wp.headline?.trim()) ||
		Boolean(wp.body?.trim()) ||
		(wp.accordionSections?.length ?? 0) > 0;

	if (!hasWpContent) {
		return {
			...defaults,
			heroImage: defaults.heroImage ?? DEFAULT_ABOUT_HERO_IMAGE,
			accordionSections: defaultSections,
		};
	}

	const wpSections = normalizeWpSections(wp.accordionSections);
	const accordionSections =
		wpSections.length > 0 ? attachSectionImages(wpSections, projects) : defaultSections;

	return {
		heroImage:
			(wp.heroImage ? (wpMediaUrl(wp.heroImage) ?? wp.heroImage) : undefined) ||
			defaults.heroImage ||
			DEFAULT_ABOUT_HERO_IMAGE,
		heroVideo: wp.heroVideo ? (wpMediaUrl(wp.heroVideo) ?? wp.heroVideo) : defaults.heroVideo,
		headline: wp.headline?.trim() ? sanitizeAboutHeadline(wp.headline) : defaults.headline,
		body: wp.body?.trim() ? sanitizeAboutBody(wp.body) : defaults.body,
		accordionSections,
	};
}
