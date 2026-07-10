import { NextResponse } from 'next/server';

import { GetCapabilitiesApi } from '../../../components/ApiWp';

export const dynamic = 'force-dynamic';

export async function GET(request: Request) {
	const cookie = request.headers.get('cookie') ?? '';
	const match = cookie.match(/(?:^|;\s*)language=([^;]+)/);
	const language = match?.[1] ?? '';

	const query: Record<string, string> = {};
	if (language) {
		query.translate = language;
	}

	const capabilities = await GetCapabilitiesApi(query);
	return NextResponse.json(capabilities);
}
