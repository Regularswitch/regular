import { NextResponse } from 'next/server';
import { GetApi } from '../../../../components/ApiWp';

export async function GET(request: Request) {
	const cookie = request.headers.get('cookie') ?? '';
	const match = cookie.match(/(?:^|;\s*)language=([^;]+)/);
	const language = match?.[1] ?? '';

	const query = {
		per_page: 22,
		translate: language,
	};

	const apiWp = await GetApi('/project-category', query);
	return NextResponse.json(apiWp.map((p) => ({ ...p, content: '' })));
}

