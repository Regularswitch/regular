import {
	getDefaultEducationContent,
	type EducationContent,
	type EducationGallery,
	type EducationGalleryLayout,
	type EducationInstitution,
} from './defaults';
import { wpMediaUrl } from '../../wp/mediaUrl';
import type { ProjectAccordionSection } from '../../projects/parseContent';

const GALLERY_LAYOUTS: EducationGalleryLayout[] = ['pair', 'triple', 'grid-2x2'];

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

function normalizeGallery(raw: unknown): EducationGallery | undefined {
	if (!raw || typeof raw !== 'object') return undefined;

	const item = raw as Record<string, unknown>;
	const layout = GALLERY_LAYOUTS.includes(item.layout as EducationGalleryLayout)
		? (item.layout as EducationGalleryLayout)
		: 'pair';

	const images = Array.isArray(item.images)
		? item.images
				.map((url) => (typeof url === 'string' ? (wpMediaUrl(url) ?? url) : ''))
				.filter(Boolean)
		: [];

	const caption = typeof item.caption === 'string' ? item.caption.trim() : undefined;

	if (!images.length && !caption) return undefined;

	return { layout, images, caption: caption || undefined };
}

function normalizeInstitutions(raw: unknown): EducationInstitution[] {
	if (!Array.isArray(raw)) return [];

	const institutions: EducationInstitution[] = [];

	for (const entry of raw) {
		if (!entry || typeof entry !== 'object') continue;
		const item = entry as Record<string, unknown>;
		const name = typeof item.name === 'string' ? item.name.trim() : '';
		if (!name) continue;

		const logoRaw = typeof item.logo === 'string' ? item.logo : '';
		const logo = logoRaw ? (wpMediaUrl(logoRaw) ?? logoRaw) : undefined;
		const description =
			typeof item.description === 'string' && item.description.trim()
				? item.description
				: undefined;

		institutions.push({
			name,
			logo,
			description,
			topGallery: normalizeGallery(item.topGallery),
			midGallery: normalizeGallery(item.midGallery),
			bottomGallery: normalizeGallery(item.bottomGallery),
		});
	}

	return institutions;
}

export function buildEducationContent(
	wp: EducationContent | null | undefined,
	locale: 'en' | 'pt',
): EducationContent {
	const defaults = getDefaultEducationContent(locale);

	if (!wp) {
		return defaults;
	}

	const wpInstitutions = normalizeInstitutions(wp.institutions);
	const hasWpContent =
		Boolean(wp.heroImage) ||
		Boolean(wp.heroVideo) ||
		Boolean(wp.headline?.trim()) ||
		(wp.accordionSections?.length ?? 0) > 0 ||
		wpInstitutions.length > 0 ||
		(wp.studioImages?.length ?? 0) > 0;

	if (!hasWpContent) {
		return defaults;
	}

	const wpSections = normalizeWpSections(wp.accordionSections);
	const studioImages = (wp.studioImages ?? [])
		.map((url) => wpMediaUrl(url) ?? url)
		.filter(Boolean);

	return {
		heroImage: wp.heroImage ? (wpMediaUrl(wp.heroImage) ?? wp.heroImage) : defaults.heroImage,
		heroVideo: wp.heroVideo ? (wpMediaUrl(wp.heroVideo) ?? wp.heroVideo) : defaults.heroVideo,
		headline: wp.headline?.trim() ? wp.headline : defaults.headline,
		accordionSections: wpSections.length > 0 ? wpSections : defaults.accordionSections,
		institutions: wpInstitutions.length > 0 ? wpInstitutions : defaults.institutions,
		studioImages: studioImages.length > 0 ? studioImages : defaults.studioImages,
	};
}
