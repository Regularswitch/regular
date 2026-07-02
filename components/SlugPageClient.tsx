'use client';

import { useEffect, useRef, useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import DateTimeComponent from './DateTimeComponent';
import parse, { DOMNode } from 'html-react-parser';
import BackgroundProject from './BackgroundProject';
import type { Category, Meta, Projects } from '../types';

type SlugPageClientProps = {
	allPosts: Projects;
	allPostCat: Projects;
	allCat: Category[];
	slug: string;
	allMetas: unknown;
};

const useScrollVisibility = () => {
	const [visible, setVisible] = useState(true);

	useEffect(() => {
		const handleScroll = () => {
			const offsetTop = document.documentElement.scrollTop || document.body.scrollTop;
			const isMobile = window.innerWidth <= 768;
			const threshold = isMobile ? 65 : 300;
			setVisible(offsetTop <= threshold);
		};

		window.addEventListener('scroll', handleScroll);
		return () => window.removeEventListener('scroll', handleScroll);
	}, []);

	return visible;
};

export default function SlugPageClient({ allPosts, allPostCat, allCat, slug, allMetas }: SlugPageClientProps) {
	const visibleBG = useScrollVisibility();
	const bgRef = useRef<HTMLDivElement | null>(null);
	const [visible, setVisible] = useState(1);

	function renderContent() {
		const content = allPosts?.[0]?.content ?? '';

		const options = {
			replace: (domNode: DOMNode) => {
				if (domNode?.type === 'comment' && String(domNode?.data ?? '').trim() === 'DATETIME_COMPONENT') {
					return <DateTimeComponent />;
				}
				return undefined;
			},
		};

		return parse(content, options);
	}

	useEffect(() => {
		const scrollShow = () => {
			const offsetTop = document.documentElement.scrollTop || document.body.scrollTop || 0;
			setVisible(offsetTop > 70 ? 0 : 1);
		};

		document.addEventListener('scroll', scrollShow);
		return () => document.removeEventListener('scroll', scrollShow);
	}, []);

	const dictionary: Record<string, string> = {
		branding: 'branding',
		'digital-and-internet': 'digital',
		work: 'home',
		'graphical-arquitecture': 'graphic-architecture',
	};

	const dictionaryColors: Record<string, string> = {
		branding: ' bg-[#00FD] ',
		'digital-and-internet': ' bg-[#0F0D] ',
		'graphical-arquitecture': ' bg-[#F00D] ',
		work: ' bg-[#000] ',
	};
	const dictionaryColorsLine: Record<string, string> = {
		branding: ' bg-[#FFF] ',
		'digital-and-internet': ' bg-[#000] ',
		'graphical-arquitecture': ' bg-[#FFF] ',
		work: ' bg-[#FFF] ',
	};
	const dictionaryColorsText: Record<string, string> = {
		branding: ' text-[#FFF] ',
		'digital-and-internet': ' text-[#000] ',
		'graphical-arquitecture': ' text-[#FFF] ',
		work: ' text-[#FFF] text-sm-rsw',
	};

	const bg =
		typeof allMetas === 'object' &&
		allMetas !== null &&
		// @ts-expect-error legacy meta shape
		Array.isArray(allMetas?.meta?.etc_project_video_thumbnail)
			? // @ts-expect-error legacy meta shape
				allMetas?.meta?.etc_project_video_thumbnail?.[0] ?? ''
			: '';
	const video =
		typeof allMetas === 'object' &&
		allMetas !== null &&
		// @ts-expect-error legacy meta shape
		Array.isArray(allMetas?.meta?.etc_project_video_thumbnail)
			? // @ts-expect-error legacy meta shape
				allMetas?.meta?.etc_project_video_thumbnail?.[0]
			: undefined;

	const [headerTextColor, setHeaderTextColor] = useState<'black' | 'white'>('black');
	const handleColorExtract = (color: string) => {
		setHeaderTextColor(color === 'white' ? 'white' : 'black');
	};

	const color = dictionaryColors?.[slug] || 'bg-[#0F0D]';
	const colorTitle = dictionaryColorsText?.[slug] || 'text-[#000]';
	const colorLine = dictionaryColorsLine?.[slug] || 'text-[#000]';

	function getName(id: number): string {
		return String(allCat.find((c) => c.id === id)?.title ?? '');
	}

	const postsWithSlugs = (allPostCat ?? []).map((post) => {
		const categorySlugs = (post.category ?? []).map((catId) => getName(catId));
		return { ...post, categorySlugs };
	});

	const filteredPosts =
		slug === 'work'
			? postsWithSlugs.filter((post) => Array.isArray(post.category) && post.category.includes(32))
			: postsWithSlugs.filter((post) => (post as any).categorySlugs?.includes(dictionary?.[slug] || slug || ''));

	return (
		<div className={slug !== 'about' && slug !== 'education' && slug !== 'contact-3' ? 'hide-height' : ''}>
			{(slug === 'education' || slug === 'about') && (
				<>
					<div className="block w-full h-auto lg:w-[90vw] mx-auto aspect-w-16 aspect-h-9" />
					<BackgroundProject bg={bg} video={video} visible={visibleBG} ref={bgRef} onColorExtract={handleColorExtract} />
				</>
			)}

			{allPosts?.[0]?.image_full ? (
				<>
					<div className={`transition-all duration-300 fixed top-0 left-0 w-[100vw] z-[-1] h-[100vh] ${visible ? 'opacity-[1]' : 'opacity-[0]'}`}>
						<Image alt={String(allPosts[0].title ?? '')} src={String(allPosts[0].image_full)} fill style={{ objectFit: 'cover' }} />
					</div>
					<div className="h-[90vh]" />
				</>
			) : null}

			<div className="lg:w-[90vw] mx-auto px-4">
				<h1 className=" text-[20px] lg:text-[70px] font-hk leading-[1em] font-extrabold py-4  lg:py-[50px]">{allPosts?.[0]?.title}</h1>
				<div>{renderContent()}</div>

				<div className="columns-1 md:columns-3 gap-8 font-hk">
					{filteredPosts.map((p) => (
						<div key={p.id} className="mb-8">
							<Link href={`/project/${p.slug}`}>
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
									<div
										className={`p-8 transition-all duration-300 opacity-0 hover:opacity-100 block absolute z-10 top-0 left-0 w-full h-[1000px]${color}`}
									>
										<strong className={`font-bold${colorTitle}`}>{p.title}</strong>
										<div className={`inline-block w-[40px] h-[1px] mb-[6px] mx-[6px]${colorLine}`} />
										<div className={colorTitle} dangerouslySetInnerHTML={{ __html: p.more ?? '' }} />
										{(p.category ?? []).map((id) => (
											<span key={id} className={`mr-2 opacity-50${colorTitle}`}>
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

