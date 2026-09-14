import {
	ABOUT_PAGE_SLUG,
	CAPABILITIES_PAGE_SLUG,
	CONTACT_PAGE_SLUG,
	EDUCATION_PAGE_SLUG,
	isProjectsPageSlug,
} from '../site/pageSlugs';
import type { SeoPostType } from './metadata';

/** Mapeia slug de rota → CPT SEO (exceto project e legal). */
export function seoPostTypeForRouteSlug(slug: string): Exclude<SeoPostType, 'project'> | null {
	if (isProjectsPageSlug(slug)) return 'projects-page';
	if (slug === EDUCATION_PAGE_SLUG) return 'education';
	if (slug === CAPABILITIES_PAGE_SLUG) return 'capabilities';
	if (slug === ABOUT_PAGE_SLUG) return 'about';
	if (slug === CONTACT_PAGE_SLUG) return 'contact';
	return null;
}
