import BrandsMarquee from '../components/BrandsMarquee/BrandsMarquee';
import { GetApi, GetBrandsApi, GetCategoriesApi, GetIntroByLocale, GetSiteUiApi } from '../components/ApiWp';
import IntroSection from '../components/Intro/IntroSection';
import SelectedProjects from '../components/SelectedProjects/SelectedProjects';
import LatestProjects from '../components/LatestProjects/LatestProjects';
import { buildSiteUiContent, resolveSiteUi } from '../lib/resolveSiteUi';
import type { Brand, Category, Projects } from '../types';
import LiquidBlob3D from '../components/LiquidBlob3D/LiquidBlob3D';

export const revalidate = 60;

export default async function HomePage() {
	const [projects, allCat, brands, intro, siteUiRaw] = await Promise.all([
		GetApi('/project/', { _embed: '', per_page: 100 }),
		GetCategoriesApi('/project-category', { per_page: 22 }),
		GetBrandsApi({ _embed: '', per_page: '100', orderby: 'menu_order', order: 'asc' }),
		GetIntroByLocale('en'),
		GetSiteUiApi(),
	]).catch((error) => {
		console.error('Failed to fetch data:', error);
		return [[], [], [], null, null] as [Projects, Category[], Brand[], null, null];
	});

	const ui = resolveSiteUi(buildSiteUiContent(siteUiRaw), 'en');

	return (
		<>
			<LiquidBlob3D
				className="rounded-xl relative h-[70svh] min-h-[520px] md:h-[78vh] grid place-items-center overflow-hidden bg-black"
				intensity={0.5}
				blobRadius={1.45}
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

			<div className="h-10" />
		</>
	);
}

