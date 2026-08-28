import type { AboutAccordionSection, AboutContent } from './defaults';
import { sanitizeAboutBody, sanitizeAboutHeadline } from '../../wp/sanitizeRichText';
import { wpMediaUrl } from '../../wp/mediaUrl';
import type { Projects } from '../../../types';

function attachSectionImages(sections: AboutAccordionSection[], projects: Projects): AboutAccordionSection[] {
	return sections.map((section) => {
		if (section.image || !section.imageProjectSlug) return section;

		const image = projects.find((project) => project.slug === section.imageProjectSlug)?.image_full;
		return image ? { ...section, image } : section;
	});
}

function hasMeaningfulHtml(html: string | undefined): boolean {
	if (!html?.trim()) return false;

	return html
		.replace(/<[^>]*>/g, '')
		.replace(/&nbsp;/gi, ' ')
		.trim().length > 0;
}

function normalizeWpSections(sections: AboutAccordionSection[] | undefined): AboutAccordionSection[] {
	if (!sections?.length) return [];

	return sections
		.filter((section) => section.title?.trim() && hasMeaningfulHtml(section.body))
		.map((section) => ({
			title: section.title.trim(),
			body: section.body ?? '',
			image: section.image ? (wpMediaUrl(section.image) ?? section.image) : undefined,
		}));
}

export function buildAboutContent(
	wp: AboutContent | null | undefined,
	_locale: 'en' | 'pt',
	projects: Projects,
): AboutContent {
	const empty: AboutContent = {
		headline: '',
		body: '',
		accordionSections: [],
	};

	if (!wp) {
		return empty;
	}

	const wpSections = normalizeWpSections(wp.accordionSections);

	return {
		heroImage: wp.heroImage ? (wpMediaUrl(wp.heroImage) ?? wp.heroImage) : undefined,
		heroVideo: wp.heroVideo ? (wpMediaUrl(wp.heroVideo) ?? wp.heroVideo) : undefined,
		headline: wp.headline?.trim() ? sanitizeAboutHeadline(wp.headline) : '',
		body: wp.body?.trim() ? sanitizeAboutBody(wp.body) : '',
		accordionSections: attachSectionImages(wpSections, projects),
	};
}
