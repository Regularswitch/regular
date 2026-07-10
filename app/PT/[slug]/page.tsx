import Link from 'next/link';
import Image from 'next/image';

import { GetIntroApi } from '../../../components/ApiWp';
import ProjectsListing from '../../../components/ProjectsListing/ProjectsListing';
import { getBaseUrl } from '../../../lib/getBaseUrl';
import type { Category, Intro, Projects } from '../../../types';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

async function fetchPtWorkPage() {
	const base = getBaseUrl();
	const headers = { Cookie: 'language=PT' };

	const [projects, categories, intro] = await Promise.all([
		fetch(`${base}/api/project`, { headers }).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`, { headers }).then((r) => r.json() as Promise<Category[]>),
		GetIntroApi({ translate: 'PT' }),
	]).catch((error) => {
		console.error('Error fetching PT work page', error);
		return [[], [], null] as [Projects, Category[], Intro | null];
	});

	return { projects, categories, intro };
}

export default async function PtSlugPage({ params }: PageProps) {
	const { slug } = await params;

	if (slug === 'work') {
		const { projects, categories, intro } = await fetchPtWorkPage();
		return <ProjectsListing projects={projects} categories={categories} intro={intro} locale="pt" />;
	}

	const base = getBaseUrl();

	const [allPosts, allCat, allPostCat] = await Promise.all([
		fetch(`${base}/api/${slug}`, { headers: { Cookie: 'language=PT' } }).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`, { headers: { Cookie: 'language=PT' } }).then((r) => r.json() as Promise<Category[]>),
		fetch(`${base}/api/project-category/${slug}`, { headers: { Cookie: 'language=PT' } }).then((r) => r.json() as Promise<Projects>),
	]).catch((error) => {
		console.error('Error fetching PT slug page', error);
		return [[], [], []] as [Projects, Category[], Projects];
	});

	const dictionary: Record<string, string> = {
		branding: 'branding',
		'digital-and-internet': 'digital',
		work: 'home',
		'graphical-arquitecture': 'graphic-architecture',
	};

	const slugWhite = new Set(['about', 'contact-3']);
	const isLight = slugWhite.has(slug);
	const bgPage = isLight ? ' bg-[#FFF] text-[#000] ' : '';
	const lightTitle = isLight ? ' text-[#000] ' : '';

	const getName = (id: number) => String((allCat as unknown as Array<{ id: number; slug?: string }>).find((c) => c.id === id)?.slug ?? '');
	const enriched = (allPostCat ?? []).map((post) => {
		const categorySlugs = (post.category ?? []).map((catId) => getName(catId));
		return { ...post, categorySlugs };
	});
	const filtered = enriched.filter((p) => (p as { categorySlugs?: string[] }).categorySlugs?.includes(dictionary?.[slug] || slug || ''));

	return (
		<div className={bgPage}>
			<div className="container lg:w-[1200px] mx-auto">
				<h1 className={`text-white text-[20px] lg:text-[70px] font-hk leading-[1em] font-extrabold py-4 px-4 lg:py-[50px]${lightTitle}`}>
					{allPosts?.[0]?.title}
				</h1>

				<div dangerouslySetInnerHTML={{ __html: allPosts?.[0]?.content ?? '' }} />
				<div className="columns-1 md:columns-3 gap-8 font-hk">
					{filtered.map((p) => (
						<div key={p.id} className="mb-8">
							<Link href={`/PT/project/${p.slug}`}>
								<div className="relative flex overflow-hidden">
									<div className="block relative w-full overflow-hidden">
										<Image
											src={String(p.image_full)}
											alt={String(p.title ?? '')}
											width={600}
											height={600}
											sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
											className="w-full h-auto transition-all duration-300 hover:scale-[1.05]"
										/>
									</div>
									<div className="p-8 transition-all duration-300 opacity-0 hover:opacity-100 block absolute z-10 top-0 left-0 bg-[#C00D] w-full h-[1000px]">
										<strong className="text-white font-bold">{p.title}</strong>
										<div className="inline-block w-[40px] h-[1px] mb-[6px] mx-[6px] bg-[#FFF] " />
										<div dangerouslySetInnerHTML={{ __html: p.more ?? '' }} />
										{(p.category ?? []).map((id) => (
											<span key={id} className="mr-2 text-[#FFF6]">
												#{getName(id)}
											</span>
										))}
									</div>
								</div>
							</Link>
						</div>
					))}
				</div>
			</div>
		</div>
	);
}
