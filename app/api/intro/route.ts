import { NextResponse } from 'next/server';

import { GetIntroByLocale } from '../../../components/ApiWp';
import type { WpLocale } from '../../../lib/wp/localeSlug';

export const dynamic = 'force-dynamic';

export async function GET(request: Request) {
	const cookie = request.headers.get('cookie') ?? '';
	const match = cookie.match(/(?:^|;\s*)language=([^;]+)/);
	const language = match?.[1] ?? '';
	const locale: WpLocale = language === 'PT' ? 'pt' : 'en';

	const intro = await GetIntroByLocale(locale);
	return NextResponse.json(intro);
}
