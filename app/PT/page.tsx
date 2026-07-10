import BrandsMarquee from '../../components/BrandsMarquee/BrandsMarquee';
import { GetBrandsApi, GetIntroByLocale, GetSiteUiApi } from '../../components/ApiWp';
import IntroSection from '../../components/Intro/IntroSection';
import SelectedProjects from '../../components/SelectedProjects/SelectedProjects';
import LatestProjects from '../../components/LatestProjects/LatestProjects';
import { buildSiteUiContent, resolveSiteUi } from '../../lib/resolveSiteUi';
import { getBaseUrl } from '../../lib/getBaseUrl';
import type { Brand, Category, Projects } from '../../types';

export const revalidate = 10;

export default async function PtHomePage() {
	const base = getBaseUrl();

	const [allPosts, allCat, brands, intro, siteUiRaw] = await Promise.all([
		fetch(`${base}/api/project`, { headers: { Cookie: 'language=PT' } }).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`, { headers: { Cookie: 'language=PT' } }).then((r) => r.json() as Promise<Category[]>),
		GetBrandsApi({ _embed: '', per_page: '100', orderby: 'menu_order', order: 'asc', translate: 'PT' }),
		GetIntroByLocale('pt'),
		GetSiteUiApi(),
	]).catch((error) => {
		console.error('Failed to fetch PT home', error);
		return [[], [], [], null, null] as [Projects, Category[], Brand[], null, null];
	});

	const ui = resolveSiteUi(buildSiteUiContent(siteUiRaw), 'pt');

	return (
		<div>
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
		</div>
	);
}

