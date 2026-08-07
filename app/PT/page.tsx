import BrandsMarquee from '../../components/BrandsMarquee/BrandsMarquee';
import {
	GetBlobVisualApi,
	GetBrandsApi,
	GetCategoriesApi,
	GetIntroByLocale,
	GetProjectsByCategorySlug,
	GetSiteUiApi,
} from '../../components/ApiWp';
import IntroSection from '../../components/Intro/IntroSection';
import SelectedProjects from '../../components/SelectedProjects/SelectedProjects';
import LatestProjects from '../../components/LatestProjects/LatestProjects';
import LiquidBlob3D from '../../components/LiquidBlob3D/LiquidBlob3D';
import { HOME_PROJECTS_CATEGORY_SLUG } from '../../lib/projects/categories';
import { resolveBlobVisual } from '../../lib/site/blobDefaults';
import { buildSiteUiContent, resolveSiteUi } from '../../lib/site/resolveSiteUi';
import type { Brand, Category, Projects } from '../../types';

export const revalidate = 60;

const HOME_SELECTED_COUNT = 5;

export default async function PtHomePage() {
	const [projects, allCat, brands, intro, siteUiRaw, blobVisualRaw] = await Promise.all([
		GetProjectsByCategorySlug(HOME_PROJECTS_CATEGORY_SLUG, {
			_embed: '',
			per_page: HOME_SELECTED_COUNT,
			translate: 'PT',
		}),
		GetCategoriesApi('/project-category', { per_page: 22, translate: 'PT' }),
		GetBrandsApi({
			_embed: '',
			per_page: '100',
			orderby: 'menu_order',
			order: 'asc',
			translate: 'PT',
		}),
		GetIntroByLocale('pt'),
		GetSiteUiApi(),
		GetBlobVisualApi(),
	]).catch((error) => {
		console.error('Failed to fetch PT home', error);
		return [[], [], [], null, null, null] as [Projects, Category[], Brand[], null, null, null];
	});

	const ui = resolveSiteUi(buildSiteUiContent(siteUiRaw), 'pt');
	const blob = resolveBlobVisual(blobVisualRaw);

	return (
		<>
			<LiquidBlob3D
				className="rounded-[5px] relative h-[50svh] min-h-[420px] md:h-[85vh] grid place-items-center overflow-hidden bg-black"
				intensity={0.5}
				blobRadius={1.45}
				color1={blob.color1}
				color2={blob.color2}
				palette={blob.palette}
			/>
			<IntroSection intro={intro} locale="pt" />

			<BrandsMarquee brands={brands} locale="pt" />

			<SelectedProjects
				projects={projects}
				categories={allCat}
				locale="pt"
				labels={ui.labels}
			/>

			<LatestProjects projects={projects} locale="pt" />
		</>
	);
}
