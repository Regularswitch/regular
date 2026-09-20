import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

/** Propaga locale da URL para o layout (schema JSON-LD PT vs EN). */
export function middleware(request: NextRequest) {
	const locale = request.nextUrl.pathname === '/PT' || request.nextUrl.pathname.startsWith('/PT/')
		? 'pt'
		: 'en';

	const headers = new Headers(request.headers);
	headers.set('x-rs-locale', locale);

	return NextResponse.next({
		request: { headers },
	});
}

export const config = {
	matcher: ['/((?!_next/static|_next/image|favicon.ico|fonts|.*\\..*).*)'],
};
