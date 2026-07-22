import BrandsMarquee from '../components/BrandsMarquee/BrandsMarquee';
import { GetApi, GetBlobVisualApi, GetBrandsApi, GetCategoriesApi, GetIntroByLocale, GetSiteUiApi } from '../components/ApiWp';
import IntroSection from '../components/Intro/IntroSection';
import SelectedProjects from '../components/SelectedProjects/SelectedProjects';
import LatestProjects from '../components/LatestProjects/LatestProjects';
import { resolveBlobVisual } from '../lib/blobDefaults';
import { buildSiteUiContent, resolveSiteUi } from '../lib/resolveSiteUi';
import type { Brand, Category, Projects } from '../types';
import LiquidBlob3D from '../components/LiquidBlob3D/LiquidBlob3D';

export const revalidate = 60;

export default async function HomePage() {
	const [projects, allCat, brands, intro, siteUiRaw, blobVisualRaw] = await Promise.all([
		GetApi('/project/', { _embed: '', per_page: 100 }),
		GetCategoriesApi('/project-category', { per_page: 22 }),
		GetBrandsApi({ _embed: '', per_page: '100', orderby: 'menu_order', order: 'asc' }),
		GetIntroByLocale('en'),
		GetSiteUiApi(),
		GetBlobVisualApi(),
	]).catch((error) => {
		console.error('Failed to fetch data:', error);
		return [[], [], [], null, null, null] as [Projects, Category[], Brand[], null, null, null];
	});

	const ui = resolveSiteUi(buildSiteUiContent(siteUiRaw), 'en');
	const blob = resolveBlobVisual(blobVisualRaw);

	return (
		<>
			<LiquidBlob3D
				className="rounded-md relative h-[50svh] min-h-[420px] md:h-[85vh] grid place-items-center overflow-hidden bg-black"
				intensity={0.5}
				blobRadius={1.45}
				color1={blob.color1}
				color2={blob.color2}
				palette={blob.palette}
			/>
			<IntroSection intro={intro} locale="en" />

			<BrandsMarquee brands={brands} locale="en" />

			<SelectedProjects
				projects={projects}
				categories={allCat}
				locale="en"
				labels={ui.labels}
			/>

			<LatestProjects projects={projects} locale="en" />

			{/* <div className="h-10" /> */}
		</>
	);
}

