import BrandsMarquee from '../../components/BrandsMarquee/BrandsMarquee';
import { GetBrandsApi, GetIntroApi } from '../../components/ApiWp';
import IntroSection from '../../components/Intro/IntroSection';
import SelectedProjects from '../../components/SelectedProjects/SelectedProjects';
import LatestProjects from '../../components/LatestProjects/LatestProjects';
import { getBaseUrl } from '../../lib/getBaseUrl';
import type { Brand, Category, Projects } from '../../types';

export const revalidate = 10;

export default async function PtHomePage() {
	const base = getBaseUrl();

	const [allPosts, allCat, brands, intro] = await Promise.all([
		fetch(`${base}/api/project`, { headers: { Cookie: 'language=PT' } }).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`, { headers: { Cookie: 'language=PT' } }).then((r) => r.json() as Promise<Category[]>),
		GetBrandsApi({ _embed: '', per_page: '100', orderby: 'menu_order', order: 'asc', translate: 'PT' }),
		GetIntroApi({ translate: 'PT' }),
	]).catch((error) => {
		console.error('Failed to fetch PT home', error);
		return [[], [], [], null] as [Projects, Category[], Brand[], null];
	});

	return (
		<div>
			<IntroSection intro={intro} locale="pt" />

			<BrandsMarquee brands={brands} />

			<SelectedProjects projects={allPosts} categories={allCat} locale="pt" />

			<LatestProjects projects={allPosts} locale="pt" />

			<div className="h-10" />
		</div>
	);
}

