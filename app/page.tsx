import BrandsMarquee from '../components/BrandsMarquee/BrandsMarquee';
import { GetBrandsApi, GetIntroApi } from '../components/ApiWp';
import IntroSection from '../components/Intro/IntroSection';
import SelectedProjects from '../components/SelectedProjects/SelectedProjects';
import LatestProjects from '../components/LatestProjects/LatestProjects';
import type { Brand, Category, Projects } from '../types';
import LiquidBlob3D from '../components/LiquidBlob3D/LiquidBlob3D';

function getBaseUrl(): string {
	if (process.env.BASE) return process.env.BASE;
	if (process.env.VERCEL_URL) return `https://${process.env.VERCEL_URL}`;
	return 'http://localhost:3000';
}

export const revalidate = 60;

export default async function HomePage() {
	const base = getBaseUrl();
	const [projects, allCat, brands, intro] = await Promise.all([
		fetch(`${base}/api/project`).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`).then((r) => r.json() as Promise<Category[]>),
		GetBrandsApi({ _embed: '', per_page: '100', orderby: 'menu_order', order: 'asc' }),
		GetIntroApi(),
	]).catch((error) => {
		console.error('Failed to fetch data:', error);
		return [[], [], [], null] as [Projects, Category[], Brand[], null];
	});

	return (
		<>
			<LiquidBlob3D
				className="rounded-xl relative h-[70svh] min-h-[520px] md:h-[78vh] grid place-items-center overflow-hidden bg-black"
				intensity={0.5}
				blobRadius={1.45}
			/>
			<IntroSection intro={intro} locale="en" />

			<BrandsMarquee title="Brands that trust us" brands={brands} />

			<SelectedProjects projects={projects} categories={allCat} locale="en" />

			<LatestProjects projects={projects} locale="en" />

			<div className="h-10" />
		</>
	);
}

