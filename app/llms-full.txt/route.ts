import { buildLlmsFullTxt } from '../../lib/seo/llmsTxt';

export const revalidate = 3600;

export function GET() {
	return new Response(buildLlmsFullTxt(), {
		headers: {
			'Content-Type': 'text/plain; charset=utf-8',
			'Cache-Control': 'public, s-maxage=3600, stale-while-revalidate=86400',
		},
	});
}
