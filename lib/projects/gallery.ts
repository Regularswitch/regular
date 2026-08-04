import { wpMediaUrl } from '../wp/mediaUrl';
import type { ProjectGalleryImage, ProjectMediaType, ProjectStructuredImage } from '../../types';

const VIDEO_EXT = /\.(mp4|webm|mov|m4v|ogg)(\?|$)/i;
const GIF_EXT = /\.gif(\?|$)/i;

export function resolveProjectMediaType(
	url: string,
	mime?: string,
	type?: ProjectMediaType | string,
): ProjectMediaType {
	if (type === 'video' || type === 'gif' || type === 'image') {
		if (type === 'image' && (mime === 'image/gif' || GIF_EXT.test(url))) return 'gif';
		return type;
	}
	if (mime?.startsWith('video/') || VIDEO_EXT.test(url)) return 'video';
	if (mime === 'image/gif' || GIF_EXT.test(url)) return 'gif';
	return 'image';
}

export function isProjectMediaVideo(
	media?: Pick<ProjectGalleryImage, 'url' | 'mime' | 'type'> | ProjectStructuredImage | null,
): boolean {
	if (!media?.url || typeof media.url !== 'string') return false;
	return resolveProjectMediaType(media.url, media.mime, media.type) === 'video';
}

/** Normaliza itens da galeria (string legada ou { url, width, height, type }). */
export function normalizeGalleryItems(
	gallery?: Array<string | ProjectGalleryImage> | null,
): ProjectGalleryImage[] {
	if (!gallery?.length) return [];

	const items: ProjectGalleryImage[] = [];

	for (const item of gallery) {
		if (typeof item === 'string') {
			const url = wpMediaUrl(item) ?? item;
			if (url) {
				items.push({
					url,
					type: resolveProjectMediaType(url),
				});
			}
			continue;
		}

		if (!item || typeof item !== 'object') continue;

		const rawUrl = typeof item.url === 'string' ? item.url : '';
		const url = rawUrl ? (wpMediaUrl(rawUrl) ?? rawUrl) : '';
		if (!url) continue;

		const width = typeof item.width === 'number' && item.width > 0 ? item.width : undefined;
		const height = typeof item.height === 'number' && item.height > 0 ? item.height : undefined;
		const mime = typeof item.mime === 'string' ? item.mime : undefined;
		const type = resolveProjectMediaType(url, mime, item.type);

		items.push({ url, width, height, mime, type });
	}

	return items;
}

export function galleryItemAspectRatio(item: ProjectGalleryImage): number | undefined {
	if (!item.width || !item.height) return undefined;
	return item.width / item.height;
}

/** Landscape bem largo → ocupa as duas colunas no grid fluido. */
export function isGalleryWide(item: ProjectGalleryImage): boolean {
	const ratio = galleryItemAspectRatio(item);
	return ratio !== undefined && ratio >= 1.7;
}
