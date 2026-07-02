import ContainerProjects from '../components/ContainerProjects';
import type { Category, Meta, Projects } from '../types';
import LiquidBlob3D from '../components/LiquidBlob3D/LiquidBlob3D';

function getBaseUrl(): string {
	if (process.env.BASE) return process.env.BASE;
	if (process.env.VERCEL_URL) return `https://${process.env.VERCEL_URL}`;
	return 'http://localhost:3000';
}

export const revalidate = 600;

export default async function HomePage() {
	const base = getBaseUrl();
	const [projects, allCat, allMetas] = await Promise.all([
		fetch(`${base}/api/project`).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`).then((r) => r.json() as Promise<Category[]>),
		fetch(`${base}/api/project/all-metas`).then((r) => r.json() as Promise<Meta[]>),
	]).catch((error) => {
		console.error('Failed to fetch data:', error);
		return [[], [], []] as [Projects, Category[], Meta[]];
	});

	return (
		<>
			<LiquidBlob3D
				className="rounded-xl relative h-[70svh] min-h-[520px] md:h-[78vh] grid place-items-center overflow-hidden bg-black"
				intensity={0.5}
				blobRadius={1.45}
			/>
			<section className="text-(--fg) container mx-auto text-[20px] lg:text-[50px] font-hk leading-[1em] font-extrabold py-4 px-4 lg:py-[150px]">
				<h2 className="block mb-[40px]">Branding / Digital / Graphic Architecture</h2>
				<p>
					RegularSwitch is a multi-cultural design agency based in Brazil. Working on the edge between analog and digital to offer visual
					experiences that matter.
				</p>
			</section>

			<div className="mx-auto p-4 lg:w-[90vw]">
				<ContainerProjects projects={projects} cats={allCat} allMetas={allMetas} />
			</div>
			<div className="h-10" />
		</>
	);
}

