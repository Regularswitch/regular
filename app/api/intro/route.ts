import { NextResponse } from 'next/server';
import { GetIntroApi } from '../../../components/ApiWp';

export const dynamic = 'force-dynamic';

export async function GET(request: Request) {
	const cookie = request.headers.get('cookie') ?? '';
	const match = cookie.match(/(?:^|;\s*)language=([^;]+)/);
	const language = match?.[1] ?? '';

	const query: Record<string, string> = {};
	if (language) {
		query.translate = language;
	}

	const intro = await GetIntroApi(query);
	return NextResponse.json(intro);
}
