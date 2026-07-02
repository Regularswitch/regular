'use client';

import { useEffect, useRef, useState } from 'react';
import HeaderComponents from './HeaderComponents';
import FooterComponents from './FooterComponents';
import BackgroundProject from './BackgroundProject';

type ProjectSlugClientProps = {
	allPosts: any[];
	lang: string;
	allMetas: any;
	slug: string;
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

export default function ProjectSlugClient({ allPosts, lang, allMetas, slug }: ProjectSlugClientProps) {
	const visible = useScrollVisibility();
	const bgRef = useRef<HTMLDivElement | null>(null);
	const [headerTextColor, setHeaderTextColor] = useState('black');

	const post = allPosts[0];
	const bg = allMetas?.img_single?.url || post?.image_full;
	const video = allMetas?.video?.url || undefined;

	const handleColorExtract = (color: string) => {
		setHeaderTextColor(color);
	};

	return (
		<div className={`font-hg ${slug !== 'about' ? 'hide-height' : ''}`}>
			<HeaderComponents lang={lang} isLight={headerTextColor === 'white'} />

			<div className="block w-full h-auto lg:w-[90vw] mx-auto aspect-w-16 aspect-h-9"></div>

			<BackgroundProject bg={bg} video={video} visible={visible} ref={bgRef} onColorExtract={handleColorExtract} />

			<div className="lg:w-[90vw] px-4 mx-auto">
				<h1 className={`text-[40px] lg:text-[70px] font-hk font-bold`}>{post.title}</h1>
				<div dangerouslySetInnerHTML={{ __html: post.content }} />
				<div className="font-hg text-black text-[30px] lg:text-[70px] font-bold cursor-pointer" onClick={() => window.history.back()}>
					←
				</div>
			</div>

			<FooterComponents />
		</div>
	);
}

