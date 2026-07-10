import { NextResponse } from 'next/server';

import { GetFooterByLocale } from '../../../components/ApiWp';
import type { WpLocale } from '../../../lib/wpLocaleSlug';

export const dynamic = 'force-dynamic';

export async function GET(request: Request) {
	const cookie = request.headers.get('cookie') ?? '';
	const match = cookie.match(/(?:^|;\s*)language=([^;]+)/);
	const language = match?.[1] ?? '';
	const locale: WpLocale = language === 'PT' ? 'pt' : 'en';

	const footer = await GetFooterByLocale(locale);
	return NextResponse.json(footer);
}
