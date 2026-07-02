import BrandsMarquee from '../../components/BrandsMarquee/BrandsMarquee';
import { GetBrandsApi, GetIntroApi } from '../../components/ApiWp';
import IntroSection from '../../components/Intro/IntroSection';
import Image from 'next/image';
import Link from 'next/link';
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

	const getName = (id: number) => allCat.find((c) => c.id === id)?.title ?? '';

	return (
		<div>
			<IntroSection intro={intro} locale="pt" />

			<BrandsMarquee brands={brands} />

			<div className="container mx-auto p-4">
				<div className="columns-1 md:columns-2 gap-4">
					{allPosts
						.filter((f) => (f.category || []).includes(17))
						.map((p) => (
							<div className="break-inside-avoid pb-4" key={p.id}>
								<Link href={`/project/${p.slug}`}>
									<div className="font-hk">
										<div className="block relative w-full overflow-hidden">
											<Image
												src={String(p.image_full)}
												alt={String(p.title ?? '')}
												width={500}
												height={500}
												sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
												className="w-full h-auto transition-all duration-300 hover:scale-[1.05]"
											/>
										</div>
										<div>
											<strong className="text-white inline-block mt-4">{p.title}</strong>
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

			<div className="h-96" />
		</div>
	);
}

