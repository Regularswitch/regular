import { GetApi } from '../../components/ApiWp';
import { wpLangSlug, type WpLocale } from '../wp/localeSlug';

type WpPage = Awaited<ReturnType<typeof GetApi>>[number];

/**
 * Busca página institucional no padrão {base}/{locale} (ex.: contact/en).
 */
export async function fetchWpPageByLocale(
	baseSlug: string,
	locale: WpLocale,
	extraQuery: Record<string, string | number> = {},
): Promise<WpPage | undefined> {
	const langSlug = wpLangSlug(locale);
	const query = { per_page: 1, ...extraQuery };

	const parents = await GetApi('/pages', { slug: baseSlug, ...query });
	const parent = parents[0];

	if (parent?.id) {
		const children = await GetApi('/pages', { slug: langSlug, parent: parent.id, ...query });
		if (children[0]) return children[0];
	}

	const legacyQuery: Record<string, string | number> = { slug: baseSlug, ...query };
	if (locale === 'pt') legacyQuery.translate = 'PT';

	const legacy = await GetApi('/pages', legacyQuery);
	if (legacy[0]) return legacy[0];

	return undefined;
}
