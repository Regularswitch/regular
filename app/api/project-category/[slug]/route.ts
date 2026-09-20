import { NextResponse } from 'next/server';
import { GetProjectsByCategorySlug } from '../../../../components/ApiWp';

export async function GET(request: Request, context: { params: Promise<{ slug: string }> }) {
	const { slug } = await context.params;
	const cookie = request.headers.get('cookie') ?? '';
	const match = cookie.match(/(?:^|;\s*)language=([^;]+)/);
	const language = match?.[1] ?? '';

	const query: Record<string, string | number> = {};
	if (language) query.translate = language;

	const apiWp = await GetProjectsByCategorySlug(slug, query);
	return NextResponse.json(apiWp);
}
