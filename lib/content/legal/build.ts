import {
	getDefaultLegalContent,
	type CookieCategory,
	type CookieCategoryId,
	type LegalContent,
} from './defaults';

const CATEGORY_IDS: CookieCategoryId[] = ['necessary', 'performance', 'functional', 'marketing'];

function normalizeCategories(raw: unknown, locale: 'en' | 'pt'): CookieCategory[] {
	const defaults = getDefaultLegalContent(locale).categories;
	const byId = new Map<string, Record<string, unknown>>();

	if (Array.isArray(raw)) {
		for (const item of raw) {
			if (!item || typeof item !== 'object') continue;
			const row = item as Record<string, unknown>;
			const id = typeof row.id === 'string' ? row.id : '';
			if (id) byId.set(id, row);
		}
	}

	return defaults.map((fallback) => {
		const row = byId.get(fallback.id);
		if (!row) return fallback;
		return {
			id: fallback.id,
			title: typeof row.title === 'string' && row.title.trim() ? row.title.trim() : fallback.title,
			description:
				typeof row.description === 'string' && row.description.trim()
					? row.description
					: fallback.description,
			locked: fallback.locked,
			defaultOn:
				typeof row.defaultOn === 'boolean' ? row.defaultOn : fallback.defaultOn,
		};
	});
}

export function buildLegalContent(
	wp: LegalContent | null | undefined,
	locale: 'en' | 'pt',
): LegalContent {
	const defaults = getDefaultLegalContent(locale);
	if (!wp) return defaults;

	return {
		privacyTitle: wp.privacyTitle?.trim() || defaults.privacyTitle,
		privacyBody: wp.privacyBody?.trim() || defaults.privacyBody,
		cookiesModalTitle: wp.cookiesModalTitle?.trim() || defaults.cookiesModalTitle,
		cookiesIntro: wp.cookiesIntro?.trim() || '',
		rejectAllLabel: wp.rejectAllLabel?.trim() || defaults.rejectAllLabel,
		submitLabel: wp.submitLabel?.trim() || defaults.submitLabel,
		categories: normalizeCategories(wp.categories, locale).filter((c) =>
			CATEGORY_IDS.includes(c.id),
		),
	};
}
