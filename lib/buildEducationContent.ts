import {
	getDefaultEducationContent,
	type EducationContent,
} from './educationDefaults';
import { wpMediaUrl } from './wpMediaUrl';
import type { ProjectAccordionSection } from './parseProjectContent';

function normalizeWpSections(
	sections: ProjectAccordionSection[] | undefined,
): ProjectAccordionSection[] {
	if (!sections?.length) return [];

	return sections
		.filter((section) => section.title?.trim())
		.map((section) => ({
			title: section.title,
			body: section.body ?? '',
		}));
}

export function buildEducationContent(
	wp: EducationContent | null | undefined,
	locale: 'en' | 'pt',
): EducationContent {
	const defaults = getDefaultEducationContent(locale);

	if (!wp) {
		return defaults;
	}

	const hasWpContent =
		Boolean(wp.heroImage) ||
		Boolean(wp.headline?.trim()) ||
		(wp.accordionSections?.length ?? 0) > 0;

	if (!hasWpContent) {
		return defaults;
	}

	const wpSections = normalizeWpSections(wp.accordionSections);

	return {
		heroImage: wp.heroImage ? (wpMediaUrl(wp.heroImage) ?? wp.heroImage) : defaults.heroImage,
		headline: wp.headline?.trim() ? wp.headline : defaults.headline,
		accordionSections: wpSections.length > 0 ? wpSections : defaults.accordionSections,
	};
}
