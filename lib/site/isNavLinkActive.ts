import { isProjectsPageSlug, PROJECTS_PAGE_SLUG } from './pageSlugs';

function normalizePath(path: string): string {
	const base = path.split('?')[0]?.split('#')[0] ?? '/';
	if (base.length > 1 && base.endsWith('/')) {
		return base.slice(0, -1);
	}
	return base || '/';
}

function stripLocalePrefix(path: string): string {
	if (path === '/PT') return '/';
	if (path.startsWith('/PT/')) {
		return path.slice(3) || '/';
	}
	return path;
}

function pathSegments(path: string): string[] {
	return normalizePath(path).split('/').filter(Boolean);
}

export function isNavLinkActive(pathname: string, href: string): boolean {
	const current = stripLocalePrefix(normalizePath(pathname));
	const target = stripLocalePrefix(normalizePath(href));

	if (current === target) {
		return true;
	}

	const targetSegments = pathSegments(target);
	const currentSegments = pathSegments(current);

	if (targetSegments.length === 0) {
		return currentSegments.length === 0;
	}

	const targetRoot = `/${targetSegments[0]}`;

	if (targetRoot === `/${PROJECTS_PAGE_SLUG}` || isProjectsPageSlug(targetSegments[0] ?? '')) {
		return currentSegments[0] === PROJECTS_PAGE_SLUG
			|| isProjectsPageSlug(currentSegments[0] ?? '')
			|| currentSegments[0] === 'project';
	}

	return current.startsWith(`${target}/`);
}
