import { NextResponse } from 'next/server';
import { GetBrandsApi } from '../../../components/ApiWp';

export const dynamic = 'force-dynamic';

export async function GET(request: Request) {
	const cookie = request.headers.get('cookie') ?? '';
	const match = cookie.match(/(?:^|;\s*)language=([^;]+)/);
	const language = match?.[1] ?? '';

	const query: Record<string, string> = {
		_embed: '',
		per_page: '100',
		orderby: 'menu_order',
		order: 'asc',
	};

	if (language) {
		query.translate = language;
	}

	const brands = await GetBrandsApi(query);
	return NextResponse.json(brands);
}
