/**
 * URL pública do front (sem trailing slash).
 * Em local/staging usa `BASE` ou a URL do deploy atual — nunca força o domínio de produção.
 */
export function getBaseUrl(): string {
	if (process.env.BASE) return process.env.BASE.replace(/\/$/, '');

	// Só em produção Vercel: domínio canônico do projeto.
	if (
		process.env.VERCEL_ENV === 'production' &&
		process.env.VERCEL_PROJECT_PRODUCTION_URL
	) {
		return `https://${process.env.VERCEL_PROJECT_PRODUCTION_URL}`.replace(/\/$/, '');
	}

	if (process.env.VERCEL_URL) return `https://${process.env.VERCEL_URL}`.replace(/\/$/, '');

	return 'http://localhost:3000';
}

