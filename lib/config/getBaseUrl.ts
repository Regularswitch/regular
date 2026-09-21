/**
 * URL pública do front (sem trailing slash).
 * Em produção evita o domínio default `*.vercel.app` quando `BASE` estiver desatualizado.
 */
export const CANONICAL_PRODUCTION_URL = 'https://regularswitch.com';

export function getBaseUrl(): string {
	if (process.env.BASE) {
		const base = process.env.BASE.replace(/\/$/, '');
		if (process.env.VERCEL_ENV === 'production' && isVercelAppHost(base)) {
			return CANONICAL_PRODUCTION_URL;
		}
		return base;
	}

	if (process.env.VERCEL_ENV === 'production') {
		const fromVercel = process.env.VERCEL_PROJECT_PRODUCTION_URL?.replace(/\/$/, '');
		if (fromVercel && !isVercelAppHost(`https://${fromVercel}`)) {
			return `https://${fromVercel}`;
		}
		return CANONICAL_PRODUCTION_URL;
	}

	if (process.env.VERCEL_URL) return `https://${process.env.VERCEL_URL}`.replace(/\/$/, '');

	return 'http://localhost:3000';
}

function isVercelAppHost(url: string): boolean {
	try {
		return /\.vercel\.app$/i.test(new URL(url).hostname);
	} catch {
		return false;
	}
}
