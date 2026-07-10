import {
	DEFAULT_CONTACT_HERO_IMAGE,
	getDefaultContactContent,
	type ContactContent,
} from './contactDefaults';
import { wpMediaUrl } from './wpMediaUrl';

export function buildContactContent(
	wp: ContactContent | null | undefined,
	locale: 'en' | 'pt',
): ContactContent {
	const defaults = getDefaultContactContent(locale);

	if (!wp) {
		return {
			...defaults,
			heroImage: defaults.heroImage ?? DEFAULT_CONTACT_HERO_IMAGE,
		};
	}

	const hasWpContent =
		Boolean(wp.heroImage) ||
		Boolean(wp.headline?.trim()) ||
		(wp.blocks?.length ?? 0) > 0;

	if (!hasWpContent) {
		return {
			...defaults,
			heroImage: defaults.heroImage ?? DEFAULT_CONTACT_HERO_IMAGE,
		};
	}

	const wpBlocks = wp.blocks?.filter((block) => block.title?.trim()) ?? [];
	const blocks = wpBlocks.length > 0 ? wpBlocks : defaults.blocks;

	return {
		heroImage:
			(wp.heroImage ? (wpMediaUrl(wp.heroImage) ?? wp.heroImage) : undefined) ||
			defaults.heroImage ||
			DEFAULT_CONTACT_HERO_IMAGE,
		headline: wp.headline?.trim() ? wp.headline : defaults.headline,
		blocks,
	};
}
