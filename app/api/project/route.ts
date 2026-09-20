import { NextResponse } from 'next/server';
import { GetApi } from '../../../components/ApiWp';

export async function GET(request: Request) {
	const cookie = request.headers.get('cookie') ?? '';
	const match = cookie.match(/(?:^|;\s*)language=([^;]+)/);
	const language = match?.[1] ?? '';

	const query = {
		_embed: '',
		per_page: 100,
		translate: language,
	};

	const apiWp = await GetApi('/project/', query);
	return NextResponse.json(apiWp.map((p) => ({ ...p, content: '' })));
}

