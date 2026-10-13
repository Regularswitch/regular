/**
 * Site em EN + PT.
 * Antes: NEXT_PUBLIC_FORCE_LOCALE=pt forçava só português.
 * Mantido desligado — o seletor EN/PT e rotas sem /PT voltam a funcionar.
 */
export function isPtOnlyMode(): boolean {
	return false;
}
