import { getDefaultContactContent, type ContactContent } from './defaults';

export function buildContactContent(
	wp: ContactContent | null | undefined,
	locale: 'en' | 'pt',
): ContactContent {
	const defaults = getDefaultContactContent(locale);

	// Feedback: página Contato sem imagem/vídeo no topo.
	if (!wp) {
		return {
			...defaults,
			heroImage: undefined,
			heroVideo: undefined,
		};
	}

	const hasWpContent = Boolean(wp.headline?.trim()) || (wp.blocks?.length ?? 0) > 0;

	if (!hasWpContent) {
		return {
			...defaults,
			heroImage: undefined,
			heroVideo: undefined,
		};
	}

	const wpBlocks = wp.blocks?.filter((block) => block.title?.trim()) ?? [];
	const blocks = wpBlocks.length > 0 ? wpBlocks : defaults.blocks;

	return {
		heroImage: undefined,
		heroVideo: undefined,
		headline: wp.headline?.trim() ? wp.headline : defaults.headline,
		blocks,
	};
}
