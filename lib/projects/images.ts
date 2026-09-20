import { wpMediaUrl } from '../wp/mediaUrl';
import { normalizeGalleryItems, resolveProjectMediaType } from './gallery';
import { normalizeYoutubeVideos } from './youtube';
import type { Project, ProjectStructuredData, ProjectStructuredImage } from '../../types';

export function structuredImageUrl(image?: ProjectStructuredImage | null): string | undefined {
	const url = image?.url;
	if (!url || typeof url !== 'string' || !url.trim()) return undefined;
	return wpMediaUrl(url) ?? url;
}

export function isGifUrl(url?: string | null): boolean {
	return Boolean(url && /\.gif(\?|$)/i.test(url));
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
		featuredImage: normalizeStructuredMedia(data.featuredImage) ?? data.featuredImage,
		gallery: normalizeGalleryItems(data.gallery),
		youtubeVideos: normalizeYoutubeVideos(data.youtubeVideos),
	};
}

/**
 * Thumbnail para cards (home / listagem de projetos).
 * Prefere a imagem destacada do WP — não o hero da página do projeto.
 */
export function getProjectHeroImage(
	project: Pick<Project, 'image_full' | 'project_data'>,
): string | undefined {
	if (project.image_full) {
		const featured = wpMediaUrl(project.image_full) ?? project.image_full;
		if (featured) return featured;
	}

	const gallery = normalizeGalleryItems(project.project_data?.gallery);
	const staticStill = gallery.find((item) => item.type === 'image');
	if (staticStill?.url) return staticStill.url;

	const galleryGif = gallery.find((item) => item.type === 'gif');
	if (galleryGif?.url) return galleryGif.url;

	return undefined;
}

/**
 * Mídia do hero na página do projeto.
 * Se o CMS colocou um GIF (pesado) e a galeria tem vídeo, usa o vídeo.
 */
export function getProjectHeroMedia(
	project: Pick<Project, 'image_full' | 'project_data'>,
	fallbackUrl?: string,
): ProjectStructuredImage | null {
	const hero = normalizeStructuredMedia(project.project_data?.heroImage) ?? null;
	const gallery = normalizeGalleryItems(project.project_data?.gallery);

	if (hero?.url) {
		const type = resolveProjectMediaType(hero.url, hero.mime, hero.type);
		if (type === 'gif') {
			const video = gallery.find((item) => item.type === 'video');
			if (video?.url) {
				return {
					url: video.url,
					mime: video.mime,
					type: 'video',
					width: video.width,
					height: video.height,
				};
			}
		}
		return hero;
	}

	if (fallbackUrl) {
		const url = wpMediaUrl(fallbackUrl) ?? fallbackUrl;
		return {
			url,
			type: resolveProjectMediaType(url),
		};
	}

	return null;
}
