import {
	getDefaultCapabilitiesContent,
	type CapabilitiesContent,
	type CapabilitySection,
} from './capabilitiesDefaults';
import type { Projects } from '../types';

function attachProjectImages(sections: CapabilitySection[], projects: Projects): CapabilitySection[] {
	return sections.map((section) => {
		if (section.image || !section.imageProjectSlug) return section;

		const image = projects.find((project) => project.slug === section.imageProjectSlug)?.image_full;
		return image ? { ...section, image } : section;
	});
}

export function buildCapabilitiesContent(
	wp: CapabilitiesContent | null | undefined,
	locale: 'en' | 'pt',
	projects: Projects,
): CapabilitiesContent {
	const defaults = getDefaultCapabilitiesContent(locale);

	if (!wp || (!wp.headline && !wp.sections?.length)) {
		return {
			headline: defaults.headline,
			sections: attachProjectImages(defaults.sections, projects),
		};
	}

	const sections =
		wp.sections?.length > 0 ? wp.sections : attachProjectImages(defaults.sections, projects);

	return {
		headline: wp.headline || defaults.headline,
		sections,
	};
}
