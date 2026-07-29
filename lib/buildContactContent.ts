import { getDefaultContactContent, type ContactContent } from './contactDefaults';
import { wpMediaUrl } from './wpMediaUrl';

export function buildContactContent(
	wp: ContactContent | null | undefined,
	locale: 'en' | 'pt',
): ContactContent {
	const defaults = getDefaultContactContent(locale);

	if (!wp) {
		return {
			...defaults,
			heroImage: undefined,
			heroVideo: undefined,
		};
	}

	const hasWpContent =
		Boolean(wp.heroImage) ||
		Boolean(wp.heroVideo) ||
		Boolean(wp.headline?.trim()) ||
		(wp.blocks?.length ?? 0) > 0;

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
		heroImage: wp.heroImage ? (wpMediaUrl(wp.heroImage) ?? wp.heroImage) : undefined,
		heroVideo: wp.heroVideo ? (wpMediaUrl(wp.heroVideo) ?? wp.heroVideo) : undefined,
		headline: wp.headline?.trim() ? wp.headline : defaults.headline,
		blocks,
	};
}
