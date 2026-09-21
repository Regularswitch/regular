/**
 * Site só em PT enquanto o EN não estiver pronto.
 * Defina NEXT_PUBLIC_FORCE_LOCALE=pt na Vercel (Production).
 * Remova ou deixe vazio para voltar ao EN+PT.
 */
export function isPtOnlyMode(): boolean {
	const raw = (process.env.NEXT_PUBLIC_FORCE_LOCALE || '').trim().toLowerCase();
	return raw === 'pt' || raw === 'pt-only' || raw === '1';
}
