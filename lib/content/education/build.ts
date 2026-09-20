import type {
	EducationContent,
	EducationGallery,
	EducationGalleryLayout,
	EducationInstitution,
} from './defaults';
import { wpMediaUrl } from '../../wp/mediaUrl';
import type { ProjectAccordionSection } from '../../projects/parseContent';

const GALLERY_LAYOUTS: EducationGalleryLayout[] = ['pair', 'triple', 'grid-2x2'];

function hasMeaningfulHtml(html: string | undefined): boolean {
	if (!html?.trim()) return false;

	return html
		.replace(/<[^>]*>/g, '')
		.replace(/&nbsp;/gi, ' ')
		.trim().length > 0;
}

function normalizeWpSections(
	sections: ProjectAccordionSection[] | undefined,
): ProjectAccordionSection[] {
	if (!sections?.length) return [];

	return sections
		.filter((section) => section.title?.trim() && hasMeaningfulHtml(section.body))
		.map((section) => ({
			title: section.title.trim(),
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
			typeof item.description === 'string' && hasMeaningfulHtml(item.description)
				? item.description
				: undefined;

		const midGallery = normalizeGallery(item.midGallery) ?? normalizeGallery(item.topGallery);

		institutions.push({
			name,
			logo,
			description,
			midGallery,
			bottomGallery: normalizeGallery(item.bottomGallery),
		});
	}

	return institutions;
}

export function buildEducationContent(
	wp: EducationContent | null | undefined,
	_locale: 'en' | 'pt',
): EducationContent {
	const empty: EducationContent = {
		headline: '',
		accordionSections: [],
	};

	if (!wp) {
		return empty;
	}

	const wpInstitutions = normalizeInstitutions(wp.institutions);
	const wpSections = normalizeWpSections(wp.accordionSections);

	return {
		heroImage: wp.heroImage ? (wpMediaUrl(wp.heroImage) ?? wp.heroImage) : undefined,
		heroVideo: wp.heroVideo ? (wpMediaUrl(wp.heroVideo) ?? wp.heroVideo) : undefined,
		headline: wp.headline?.trim() ?? '',
		accordionSections: wpSections,
		institutions: wpInstitutions.length > 0 ? wpInstitutions : undefined,
	};
}
