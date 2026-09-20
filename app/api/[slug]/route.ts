import { NextResponse } from 'next/server';

import { fetchWpPageByLocale } from '../../../lib/fetch/wpPageByLocale';
import type { WpLocale } from '../../../lib/wp/localeSlug';

import { resolveWpPageBaseSlug } from '../../../lib/site/pageSlugs';

export async function GET(request: Request, context: { params: Promise<{ slug: string }> }) {
	const { slug } = await context.params;
	const cookie = request.headers.get('cookie') ?? '';
	const match = cookie.match(/(?:^|;\s*)language=([^;]+)/);
	const language = match?.[1] ?? '';
	const locale: WpLocale = language === 'PT' ? 'pt' : 'en';

	const wpBaseSlug = resolveWpPageBaseSlug(slug);
	if (wpBaseSlug) {
		const page = await fetchWpPageByLocale(wpBaseSlug, locale, { _embed: '' });
		return NextResponse.json(page ? [page] : []);
	}

	const { GetApi } = await import('../../../components/ApiWp');
	const apiWp = await GetApi('/pages', {
		slug,
		_embed: '',
		...(locale === 'pt' ? { translate: 'PT' } : {}),
	});

	return NextResponse.json(apiWp);
}
