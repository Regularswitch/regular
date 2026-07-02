'use client';

import { useEffect, useState } from 'react';
import Image from 'next/image';

type PtProjectSlugClientProps = {
	allPosts: any[];
	lang: string;
};

export default function PtProjectSlugClient({ allPosts, lang }: PtProjectSlugClientProps) {
	const [visible, setVisible] = useState(1);

	useEffect(() => {
		const scrollShow = () => {
			const offsetTop = document.documentElement.scrollTop || document.body.scrollTop || 0;
			setVisible(offsetTop > 70 ? 0 : 1);
		};

		document.addEventListener('scroll', scrollShow);
		return () => document.removeEventListener('scroll', scrollShow);
	}, []);

	const post = allPosts?.[0];
	if (!post) return null;

	return (
		<div>
			<div className="block w-full h-[100vh]" />
			<div>
				<div className={`transition-all duration-300 fixed top-0 left-0 w-[100vw] z-[-1] h-[100vh] ${visible ? 'opacity-[1]' : 'opacity-[0]'}`}>
					<Image alt={post.title} src={post.image_full} fill style={{ objectFit: 'cover' }} />
				</div>
				<div className="container">
					<h1 className="text-white text-[70px] font-hk font-bold">{post.title}</h1>
					<div dangerouslySetInnerHTML={{ __html: post.content }} />
				</div>
			</div>
		</div>
	);
}

