import BrandsMarquee from '../components/BrandsMarquee/BrandsMarquee';
import {
	GetBlobVisualApi,
	GetBrandsApi,
	GetCategoriesApi,
	GetIntroByLocale,
	GetProjectsByCategorySlug,
	GetSiteUiApi,
} from '../components/ApiWp';
import IntroSection from '../components/Intro/IntroSection';
import SelectedProjects from '../components/SelectedProjects/SelectedProjects';
import HomeVideoSection from '../components/HomeVideo/HomeVideoSection';
import LiquidBlob3D from '../components/LiquidBlob3D/LiquidBlob3D';
import { HOME_PROJECTS_CATEGORY_SLUG } from '../lib/projects/categories';
import { fetchSectionSeo, sectionSeoFallbacks } from '../lib/seo/fetch';
import { buildPageMetadata } from '../lib/seo/metadata';
import { resolveBlobVisual } from '../lib/site/blobDefaults';
import { buildSiteUiContent, resolveSiteUi } from '../lib/site/resolveSiteUi';
import type { Brand, Category, Projects } from '../types';

export const revalidate = 60;

/** Selected home — destaque + carrossel (até 4 cards). */
const HOME_SELECTED_COUNT = 5;

export async function generateMetadata() {
	const seo = await fetchSectionSeo('intro', 'en');
	const fallback = sectionSeoFallbacks('intro', 'en');
	return buildPageMetadata(seo, {
		fallbackTitle: fallback.title,
		fallbackDescription: fallback.description,
		locale: 'en',
		path: '/',
	});
}

export default async function HomePage() {
	const [homeProjects, allCat, brands, intro, siteUiRaw, blobVisualRaw] = await Promise.all([
		GetProjectsByCategorySlug(HOME_PROJECTS_CATEGORY_SLUG, {
			_embed: '',
			per_page: HOME_SELECTED_COUNT,
		}).catch(() => [] as Projects),
		GetCategoriesApi('/project-category', { per_page: 22 }).catch(() => [] as Category[]),
		GetBrandsApi({ _embed: '', per_page: '100', orderby: 'menu_order', order: 'asc' }).catch(
			() => [] as Brand[],
		),
		GetIntroByLocale('en').catch(() => null),
		GetSiteUiApi().catch(() => null),
		GetBlobVisualApi().catch(() => null),
	]);

	const ui = resolveSiteUi(buildSiteUiContent(siteUiRaw), 'en');
	const blob = resolveBlobVisual(blobVisualRaw);

	return (
		<>
			{blob.enabled ? (
				<LiquidBlob3D
					className="rounded-[5px] relative h-[50svh] min-h-[420px] md:h-[85vh] grid place-items-center overflow-hidden bg-black"
					intensity={0.5}
					blobRadius={1.45}
					color1={blob.color1}
					color2={blob.color2}
					palette={blob.palette}
				/>
			) : null}
			<IntroSection intro={intro} locale="en" />

			<BrandsMarquee brands={brands} locale="en" />

			<SelectedProjects
				projects={homeProjects}
				categories={allCat}
				locale="en"
				labels={ui.labels}
			/>

			<HomeVideoSection video={blob.video} poster={blob.poster} locale="en" />
		</>
	);
}
