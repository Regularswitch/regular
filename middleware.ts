import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

import { isPtOnlyMode } from './lib/site/localeMode';

function shouldSkipLocaleRedirect(pathname: string): boolean {
	if (pathname.startsWith('/api/')) return true;
	if (pathname.startsWith('/_next')) return true;
	if (pathname === '/robots.txt' || pathname === '/sitemap.xml') return true;
	return false;
}

function toPtPath(pathname: string): string {
	if (pathname === '/PT' || pathname.startsWith('/PT/')) return pathname;
	if (pathname === '/') return '/PT';
	return `/PT${pathname}`;
}

/** Propaga locale da URL para o layout (schema JSON-LD PT vs EN). */
export function middleware(request: NextRequest) {
	const { pathname } = request.nextUrl;

	if (isPtOnlyMode() && !shouldSkipLocaleRedirect(pathname)) {
		const ptPath = toPtPath(pathname);
		if (ptPath !== pathname) {
			const url = request.nextUrl.clone();
			url.pathname = ptPath;
			const response = NextResponse.redirect(url, 308);
			response.cookies.set('language', 'PT', { path: '/', sameSite: 'lax' });
			return response;
		}
	}

	const locale = pathname === '/PT' || pathname.startsWith('/PT/') ? 'pt' : 'en';

	const headers = new Headers(request.headers);
	headers.set('x-rs-locale', locale);

	const response = NextResponse.next({
		request: { headers },
	});

	if (isPtOnlyMode()) {
		response.cookies.set('language', 'PT', { path: '/', sameSite: 'lax' });
	}

	return response;
}

export const config = {
	matcher: ['/((?!_next/static|_next/image|favicon.ico|fonts|.*\\..*).*)'],
};
