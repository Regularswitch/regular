import { NextResponse } from 'next/server';
import { GetApi } from '../../../../components/ApiWp';

export async function GET(request: Request, context: { params: Promise<{ slug: string }> }) {
	const { slug } = await context.params;
	const cookie = request.headers.get('cookie') ?? '';
	const match = cookie.match(/(?:^|;\s*)language=([^;]+)/);
	const language = match?.[1] ?? '';

	const query = {
		slug,
		_embed: '',
		translate: language,
		meta: '1',
	};

	const apiWp = await GetApi('/project/', query);
	return NextResponse.json(apiWp);
}

