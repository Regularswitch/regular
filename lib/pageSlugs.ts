/** Rotas Next.js (slug na URL pública). */
export const WORK_PAGE_SLUG = 'work';
export const EDUCATION_PAGE_SLUG = 'education';
export const CAPABILITIES_PAGE_SLUG = 'capabilities';
export const ABOUT_PAGE_SLUG = 'about-us';
export const CONTACT_PAGE_SLUG = 'contact';
export const PRIVACY_POLICY_PAGE_SLUG = 'privacy-policy';
export const COOKIES_POLICY_PAGE_SLUG = 'cookies-policy';

/** Slug da página pai no WordPress quando difere da rota Next.js. */
export const ABOUT_WP_BASE_SLUG = 'about';

export const LEGAL_PAGE_SLUGS = [PRIVACY_POLICY_PAGE_SLUG, COOKIES_POLICY_PAGE_SLUG] as const;

export type LegalPageSlug = (typeof LEGAL_PAGE_SLUGS)[number];

const WP_PAGE_BASE_BY_ROUTE: Record<string, string> = {
	[ABOUT_PAGE_SLUG]: ABOUT_WP_BASE_SLUG,
	[CONTACT_PAGE_SLUG]: CONTACT_PAGE_SLUG,
	[EDUCATION_PAGE_SLUG]: EDUCATION_PAGE_SLUG,
};

/** Mapeia slug da rota Next.js para o base slug da página no WordPress. */
export function resolveWpPageBaseSlug(routeSlug: string): string | null {
	return WP_PAGE_BASE_BY_ROUTE[routeSlug] ?? null;
}

export function isLegalPageSlug(slug: string): slug is LegalPageSlug {
	return (LEGAL_PAGE_SLUGS as readonly string[]).includes(slug);
}

export function pagePath(slug: string): string {
	return `/${slug}`;
}
