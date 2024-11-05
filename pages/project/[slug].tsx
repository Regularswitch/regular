import React, { useEffect, useState, useRef } from 'react';
import HeaderComponents from "../../components/HeaderComponents";
import FooterComponents from "../../components/FooterComponents";
import BackgroundProject from "../../components/BackgroundProject";
import Language from "../../components/Language";

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

export default function ProjectBySlug({ allPosts, lang, allMetas }: any) {
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
		<div className="font-hg">
			<HeaderComponents lang={lang} isLight={headerTextColor === 'white'} />

			<div className="block w-full h-auto lg:w-[90vw] mx-auto aspect-w-16 aspect-h-9"></div>

			<BackgroundProject bg={bg} video={video} visible={visible} ref={bgRef} onColorExtract={handleColorExtract} />

			<div className="lg:w-[90vw] px-4 mx-auto">
				<h1 className={`text-[40px] lg:text-[70px] font-hk font-bold`}>{post.title}</h1>
				<div dangerouslySetInnerHTML={{ __html: post.content }} />
				<div
					className="font-hg text-black text-[30px] lg:text-[70px] font-bold cursor-pointer"
					onClick={() => window.history.back()}
				>
					←
				</div>
			</div>

			<FooterComponents />
		</div>
	);
};

export async function getStaticPaths() {
	return {
		paths: [],
		fallback: 'blocking'
	}
}

export async function getStaticProps(context: { params: { slug: string }; req: { cookies: { language: string } } }) {
	const { slug } = context.params;
	const base = process.env.BASE || '';
	const lang = context.req?.cookies?.language || 'PT';

	const postsUrl = `${base}/api/project/${slug}`;
	const metasUrl = `${base}/api/project/all-metas`;

	let allPosts = [];
	let allMetas = null;

	try {
		const postsResponse = await fetch(postsUrl);
		allPosts = await postsResponse.json();

		const metasResponse = await fetch(metasUrl);
		const allMetasData = await metasResponse.json();
		allMetas = allMetasData.find((meta: any) => meta.slug === slug);
	} catch (error) {
		console.error('Error fetching project data', error);
	}

	return {
		props: {
			allPosts,
			allMetas,
			lang,
		},
		revalidate: 10
	};
}
