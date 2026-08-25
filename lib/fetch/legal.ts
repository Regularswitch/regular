import { GetLegalByLocale } from '../../components/ApiWp';
import { isLegalPageSlug, type LegalPageSlug } from '../site/pageSlugs';
import type { LegalContent } from '../content/legal/defaults';

export { isLegalPageSlug };

export type LegalPageDocument = {
	title: string;
	content: string;
};

function cookiesPolicyHtml(legal: LegalContent): string {
	const intro = legal.cookiesIntro?.trim()
		? `<div class="legal-cookies-intro">${legal.cookiesIntro}</div>`
		: '';
	const categories = (legal.categories ?? [])
		.map(
			(cat) =>
				`<section class="legal-cookie-category"><h2>${cat.title}</h2>${cat.description || ''}</section>`,
		)
		.join('');
	return `${intro}${categories}`;
}

export async function fetchLegalPage(
	slug: string,
	locale: 'en' | 'pt',
): Promise<LegalPageDocument | null> {
	if (!isLegalPageSlug(slug)) return null;

	const legal = await GetLegalByLocale(locale);
	const key = slug as LegalPageSlug;

	if (key === 'privacy-policy') {
		const content = legal.privacyBody?.trim();
		if (!content) return null;
		return {
			title: legal.privacyTitle,
			content,
		};
	}

	if (key === 'cookies-policy') {
		const content = cookiesPolicyHtml(legal).trim();
		if (!content) return null;
		return {
			title: legal.cookiesModalTitle,
			content,
		};
	}

	return null;
}
