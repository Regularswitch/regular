export type WpLocale = 'en' | 'pt';

/** Slug do post no WP (permalink: /{cpt}/{lang}/ ou /{page}/{lang}/). */
export function wpLangSlug(locale: WpLocale): string {
	return locale;
}
