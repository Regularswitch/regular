import BrandsMarquee from '../../components/BrandsMarquee/BrandsMarquee';
import { GetBrandsApi, GetBlobVisualApi, GetIntroByLocale, GetSiteUiApi } from '../../components/ApiWp';
import IntroSection from '../../components/Intro/IntroSection';
import SelectedProjects from '../../components/SelectedProjects/SelectedProjects';
import LatestProjects from '../../components/LatestProjects/LatestProjects';
import LiquidBlob3D from '../../components/LiquidBlob3D/LiquidBlob3D';
import { resolveBlobVisual } from '../../lib/blobDefaults';
import { buildSiteUiContent, resolveSiteUi } from '../../lib/resolveSiteUi';
import { getBaseUrl } from '../../lib/getBaseUrl';
import type { Brand, Category, Projects } from '../../types';

export const revalidate = 10;

export default async function PtHomePage() {
	const base = getBaseUrl();

	const [allPosts, allCat, brands, intro, siteUiRaw, blobVisualRaw] = await Promise.all([
		fetch(`${base}/api/project`, { headers: { Cookie: 'language=PT' } }).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`, { headers: { Cookie: 'language=PT' } }).then((r) => r.json() as Promise<Category[]>),
		GetBrandsApi({ _embed: '', per_page: '100', orderby: 'menu_order', order: 'asc', translate: 'PT' }),
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
				className="rounded-xl relative h-[70svh] min-h-[520px] md:h-[78vh] grid place-items-center overflow-hidden bg-black"
				intensity={0.5}
				blobRadius={1.45}
				color1={blob.color1}
				color2={blob.color2}
				palette={blob.palette}
			/>
			<IntroSection intro={intro} locale="pt" />

			<BrandsMarquee brands={brands} locale="pt" />

			<SelectedProjects
				projects={allPosts}
				categories={allCat}
				locale="pt"
				labels={ui.labels}
			/>

			<LatestProjects projects={allPosts} locale="pt" />

			<div className="h-10" />
		</>
	);
}

