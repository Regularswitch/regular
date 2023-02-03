import React, { useEffect, useState } from 'react'
import HeaderComponents from "../../components/HeaderComponents";
import FooterComponents from "../../components/FooterComponents";
import Image from 'next/image'
import Language from "../../components/Language";

export default function ProjectBySlug({ allPosts, lang }: any) {
	const [visible, setVisible] = useState(1)


	useEffect(() => {
		if (document) {
			document.addEventListener("scroll", scrollShow)
		}
	}, [])

	function scrollShow() {
		const offsetTop = document.documentElement.scrollTop || document.body.scrollTop || 0
		if (offsetTop > 70) {
			setVisible(0)
		} else {
			setVisible(1)
		}
	}

	allPosts = [allPosts[0]]

	return (
		<div className='font-hg'>
			<HeaderComponents lang={lang} />
			<div className="block w-full h-[100vh]  lg:w-[90vw] mx-auto"></div>
			{allPosts.map((p: any) => (
				<div key={p.id}>
					<div className={"transition-all duration-300 fixed top-0 left-0 w-[100vw] z-[-1] h-[100vh] " + (visible ? 'opacity-[1]' : 'opacity-[0]')}>
						<Image
							alt={p.title}
							src={p.image_full}
							layout="fill"
							objectFit="cover"
							priority
						/>
					</div>
					<div className="lg:w-[90vw] px-4 mx-auto">
						<h1 className="text-white text-[40px] lg:text-[70px] font-hk font-bold">{p.title}</h1>
						<div dangerouslySetInnerHTML={{ __html: p.content }} />
						<div className='font-hg text-white text-[30px] lg:text-[70px] font-bold cursor-pointer'
							onClick={
								() => window.history.back()
							}>
							Back to works →
						</div>
					</div>


				</div>
			))}


			<FooterComponents />
		</div>
	);
}

export async function getStaticPaths() {
	return {
		paths: [],
		fallback: 'blocking'
	}
}

export async function getStaticProps(req: any) {
	const { slug } = req.params;
	let base = process.env?.BASE
	let url = base + "/api/project/" + slug
	let allPosts = []
	let lang = req.cookies?.['language'] || 'PT'
	try {
		let requestPosts = await fetch(url)
		allPosts = await requestPosts.json()
	} catch (error) { }
	return {
		props: {
			allPosts,
			lang
		},
		revalidate: 10
	}
}
