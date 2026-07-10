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

function mergeCapabilitySections(
	parsed: CapabilitySection[],
	defaults: CapabilitySection[],
): CapabilitySection[] {
	if (!parsed.length) return defaults;

	return parsed.map((section, index) => {
		const fallback = defaults.find((item) => item.title === section.title) ?? defaults[index];

		return {
			...section,
			servicesTitle: section.servicesTitle || fallback?.servicesTitle,
			imageProjectSlug: section.imageProjectSlug || fallback?.imageProjectSlug,
		};
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

	return {
		headline: wp.headline || defaults.headline,
		sections: attachProjectImages(mergeCapabilitySections(wp.sections, defaults.sections), projects),
	};
}
