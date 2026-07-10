import {
	DEFAULT_CONTACT_HERO_IMAGE,
	getDefaultContactContent,
	type ContactContent,
} from './contactDefaults';
import { fetchWpPageByLocale } from './fetchWpPageByLocale';
import { CONTACT_PAGE_SLUG } from './pageSlugs';
import {
	extractHeroImageFromHtml,
	parsePageBlocksFromHeadings,
	parsePageHeadline,
} from './parsePageContent';

function buildContactContent(
	pageContent: string | undefined,
	pageImage: string | undefined,
	locale: 'en' | 'pt',
): ContactContent {
	const defaults = getDefaultContactContent(locale);
	const html = pageContent ?? '';

	const heroImage =
		pageImage || extractHeroImageFromHtml(html) || defaults.heroImage || DEFAULT_CONTACT_HERO_IMAGE;

	const headline = parsePageHeadline(html) ?? defaults.headline;
	const parsedBlocks = parsePageBlocksFromHeadings(html);

	return {
		heroImage,
		headline,
		blocks: parsedBlocks.length ? parsedBlocks : defaults.blocks,
	};
}

export async function fetchContactPage(locale: 'en' | 'pt') {
	return fetchContactFromWp(locale).catch((error) => {
		console.error('Error fetching contact page', error);
		return {
			content: {
				...getDefaultContactContent(locale),
				heroImage: DEFAULT_CONTACT_HERO_IMAGE,
			},
		};
	});
}

async function fetchContactFromWp(locale: 'en' | 'pt') {
	const page = await fetchWpPageByLocale(CONTACT_PAGE_SLUG, locale, { _embed: '' });

	return {
		content: buildContactContent(page?.content, page?.image_full, locale),
	};
}
