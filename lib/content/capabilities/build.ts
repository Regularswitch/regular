import type { CapabilitiesContent, CapabilitySection } from './defaults';
import type { Projects } from '../../../types';

function attachProjectImages(sections: CapabilitySection[], projects: Projects): CapabilitySection[] {
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

function normalizeWpSections(sections: CapabilitySection[] | undefined): CapabilitySection[] {
	if (!sections?.length) return [];

	return sections.filter(
		(section) => section.title?.trim() && hasMeaningfulHtml(section.body),
	);
}

export function buildCapabilitiesContent(
	wp: CapabilitiesContent | null | undefined,
	_locale: 'en' | 'pt',
	projects: Projects,
): CapabilitiesContent {
	const empty: CapabilitiesContent = {
		headline: '',
		sections: [],
	};

	if (!wp) {
		return empty;
	}

	const sections = normalizeWpSections(wp.sections);

	return {
		headline: wp.headline?.trim() ?? '',
		sections: attachProjectImages(sections, projects),
	};
}
