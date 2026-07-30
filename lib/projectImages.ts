import { wpMediaUrl } from './wpMediaUrl';
import { isProjectMediaVideo, normalizeGalleryItems, resolveProjectMediaType } from './galleryImages';
import type { Project, ProjectStructuredData, ProjectStructuredImage } from '../types';

export function structuredImageUrl(image?: ProjectStructuredImage | null): string | undefined {
	const url = image?.url;
	if (!url || typeof url !== 'string' || !url.trim()) return undefined;
	return wpMediaUrl(url) ?? url;
}

function normalizeStructuredMedia(
	image?: ProjectStructuredImage | null,
): ProjectStructuredImage | null | undefined {
	if (!image?.url || typeof image.url !== 'string') return image;
	const url = structuredImageUrl(image) ?? image.url;
	const mime = typeof image.mime === 'string' ? image.mime : undefined;
	return {
		...image,
		url,
		mime,
		type: resolveProjectMediaType(url, mime, image.type),
	};
}

export function normalizeProjectData(
	data?: ProjectStructuredData | null,
): ProjectStructuredData | null {
	if (!data) return null;

	return {
		...data,
		heroImage: normalizeStructuredMedia(data.heroImage) ?? data.heroImage,
		logoImage: normalizeStructuredMedia(data.logoImage) ?? data.logoImage,
		gallery: normalizeGalleryItems(data.gallery),
	};
}

/**
 * Thumbnail estático para cards (home / listagem).
 * Ignora vídeo no hero e pega a primeira imagem/GIF da galeria se precisar.
 */
export function getProjectHeroImage(
	project: Pick<Project, 'image_full' | 'project_data'>,
): string | undefined {
	const hero = project.project_data?.heroImage;
	if (hero && !isProjectMediaVideo(hero)) {
		const url = structuredImageUrl(hero);
		if (url) return url;
	}

	const gallery = normalizeGalleryItems(project.project_data?.gallery);
	const still = gallery.find((item) => item.type !== 'video');
	if (still?.url) return still.url;

	if (project.image_full) return wpMediaUrl(project.image_full) ?? project.image_full;
	return undefined;
}
