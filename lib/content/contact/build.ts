import type { ContactContent } from './defaults';

export function buildContactContent(
	wp: ContactContent | null | undefined,
	_locale: 'en' | 'pt',
): ContactContent {
	const empty: ContactContent = {
		headline: '',
		blocks: [],
	};

	if (!wp) {
		return empty;
	}

	const blocks = wp.blocks?.filter((block) => block.title?.trim()) ?? [];

	return {
		heroImage: undefined,
		heroVideo: undefined,
		headline: wp.headline?.trim() ?? '',
		blocks,
	};
}
