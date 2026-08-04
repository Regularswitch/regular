export function getBaseUrl(): string {
	if (process.env.BASE) return process.env.BASE;
	if (process.env.VERCEL_URL) return `https://${process.env.VERCEL_URL}`;
	return 'http://localhost:3000';
}

