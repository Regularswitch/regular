import { GetApi } from '../components/ApiWp';
import {
	DEFAULT_CONTACT_HERO_IMAGE,
	getDefaultContactContent,
	type ContactContent,
} from './contactDefaults';
import { extractHeroImageFromHtml } from './parsePageContent';
import { getBaseUrl } from './getBaseUrl';

function buildContactContent(
	pageContent: string | undefined,
	pageImage: string | undefined,
	locale: 'en' | 'pt',
): ContactContent {
	const defaults = getDefaultContactContent(locale);
	const html = pageContent ?? '';

	const heroImage =
		pageImage || extractHeroImageFromHtml(html) || defaults.heroImage || DEFAULT_CONTACT_HERO_IMAGE;

	return {
		heroImage,
		headline: defaults.headline,
		blocks: defaults.blocks,
	};
}

async function fetchContactFromWp(translate?: string) {
	const query: Record<string, string | number> = { _embed: '' };
	if (translate) query.translate = translate;

	const pages = await GetApi('/pages', { slug: 'contact-3', ...query });
	const page = pages[0];
	const locale = translate === 'PT' ? 'pt' : 'en';

	return {
		content: buildContactContent(page?.content, page?.image_full, locale),
	};
}

export async function fetchContactPage(locale: 'en' | 'pt') {
	if (locale === 'en') {
		return fetchContactFromWp().catch((error) => {
			console.error('Error fetching contact page', error);
			return {
				content: {
					...getDefaultContactContent('en'),
					heroImage: DEFAULT_CONTACT_HERO_IMAGE,
				},
			};
		});
	}

	const base = getBaseUrl();
	const headers = { Cookie: 'language=PT' };

	try {
		const pages = await fetch(`${base}/api/contact-3`, { headers }).then(
			(r) => r.json() as Promise<Array<{ content?: string; image_full?: string }>>,
		);
		const page = pages[0];

		return {
			content: buildContactContent(page?.content, page?.image_full, 'pt'),
		};
	} catch (error) {
		console.error('Error fetching PT contact page', error);
		return {
			content: {
				...getDefaultContactContent('pt'),
				heroImage: DEFAULT_CONTACT_HERO_IMAGE,
			},
		};
	}
}
