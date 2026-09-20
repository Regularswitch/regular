import type { ProjectStructuredData } from '../../types';

export type ProjectYoutubeVideo = {
	id: string;
	url: string;
};

const YOUTUBE_ID = /^[A-Za-z0-9_-]{11}$/;
const YOUTUBE_URL =
	/(?:youtube\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/i;

export function parseYouTubeId(value?: string | null): string | undefined {
	if (!value || typeof value !== 'string') return undefined;
	const trimmed = value.trim();
	if (!trimmed) return undefined;
	if (YOUTUBE_ID.test(trimmed)) return trimmed;

	try {
		const href = trimmed.startsWith('http') ? trimmed : `https://${trimmed}`;
		const parsed = new URL(href);
		const fromQuery = parsed.searchParams.get('v');
		if (fromQuery && YOUTUBE_ID.test(fromQuery)) return fromQuery;
	} catch {
		// segue para o regex
	}

	const match = trimmed.match(YOUTUBE_URL);
	return match?.[1];
}

export function youtubeEmbedUrl(id: string): string {
	return `https://www.youtube-nocookie.com/embed/${id}`;
}

export function normalizeYoutubeVideos(
	videos?: ProjectStructuredData['youtubeVideos'] | string[] | null,
): ProjectYoutubeVideo[] {
	if (!videos?.length) return [];

	const items: ProjectYoutubeVideo[] = [];
	const seen = new Set<string>();

	for (const item of videos) {
		const raw = typeof item === 'string' ? item : item?.url || item?.id;
		const id = parseYouTubeId(raw) ?? (typeof item === 'object' && item?.id ? parseYouTubeId(item.id) : undefined);
		if (!id || seen.has(id)) continue;
		seen.add(id);
		const url =
			typeof item === 'object' && typeof item.url === 'string' && item.url.trim()
				? item.url.trim()
				: `https://www.youtube.com/watch?v=${id}`;
		items.push({ id, url });
	}

	return items;
}
