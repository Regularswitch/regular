import React, { useEffect, useState } from 'react'
import HeaderComponents from "../components/HeaderComponents";
import FooterComponents from "../components/FooterComponents";
import Link from 'next/link'
import Image from 'next/image'

export default function ProjectBySlug({ allPosts, allPostCat, allCat, slug }: any) {

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

	let dictionary: any = {
		'branding': 'branding',
		'digital-and-internet': 'digital',
		'work': 'home',
		'graphical-arquitecture': 'graphic-architecture',
	}

	let dictionaryColors: any = {
		'branding': ' bg-[#00FD] ',
		'digital-and-internet': ' bg-[#0F0D] ',
		'graphical-arquitecture': ' bg-[#F00D] ',
		'work': ' bg-[#FF0D] ',
	}
	let dictionaryColorsLine: any = {
		'branding': ' bg-[#FFF] ',
		'digital-and-internet': ' bg-[#000] ',
		'graphical-arquitecture': ' bg-[#FFF] ',
		'work': ' bg-[#000] ',
	}
	let dictionaryColorsText: any = {
		'branding': ' text-[#FFF] ',
		'digital-and-internet': ' text-[#000] ',
		'graphical-arquitecture': ' text-[#FFF] ',
		'work': ' text-[#000] ',
	}

	const color = dictionaryColors?.[slug] || 'bg-[#0F0D]'
	const colorTitle = dictionaryColorsText?.[slug] || 'text-[#000]'
	const colorLine = dictionaryColorsLine?.[slug] || 'text-[#000]'

	const slugWhite = ['about', 'contact-3']
	const isLight = slugWhite.includes(slug)
	const bgPage = isLight ? " bg-[#FFF] text-[#000] " : ''
	const lightTitle = isLight ? " text-[#000] " : 'text-[#FFF]'
	function getName(id: any): string {
		return allCat.find((c: any) => c.id == id).slug
	}
	allPostCat = allPostCat.map((post: any) => {
		post.categorySlugs = post?.category?.map((catId: number) => getName(catId)) || []
		return post
	})
	allPostCat = allPostCat.filter((f: any) => f.categorySlugs.includes(dictionary?.[slug] || slug || ''))
	return (
		<div className={bgPage}>
			{isLight && <style>
				{`
				body {
					background-color: #FFF !important;
				}
			`}
			</style>}
			<HeaderComponents isLight={isLight} />
			{allPosts[0].image_full && <>
				<div className={"transition-all duration-300 fixed top-0 left-0 w-[100vw] z-[-1] h-[100vh] " + (visible ? 'opacity-[1]' : 'opacity-[0]')}>
					<Image
						alt={allPosts[0].title}
						src={allPosts[0].image_full}
						layout="fill"
						objectFit="cover"
					/>
				</div>
				<div className='h-[90vh]'></div>
			</>}
			<div className="container lg:w-[1200px] mx-auto px-4">
				<h1 className={" text-[20px] lg:text-[70px] font-hk leading-[1em] font-extrabold py-4  lg:py-[50px]" + lightTitle}>
					{allPosts[0].title}
				</h1>

				<div dangerouslySetInnerHTML={{ __html: allPosts[0].content }} />
				<div className="columns-1 md:columns-3 gap-8 font-hk">
					{allPostCat && allPostCat.map((p: any) => <div
						key={p.id}
						className="mb-8"
					>
						<Link href={'project/' + p.slug} >
							<div className="relative flex overflow-hidden">
								<div className="block relative w-full overflow-hidden">
									<img className=" w-full transition-all  duration-300 hover:scale-[1.05]" src={p.image_full} alt={p.title} />
								</div>
								<div
									className={"p-8 transition-all duration-300 opacity-0 hover:opacity-100 block absolute z-10 top-0 left-0 w-full h-[1000px]" + color}
								>
									<strong className={"font-bold" + colorTitle}>{p.title}</strong>
									<div className={"inline-block w-[40px] h-[1px] mb-[6px] mx-[6px]" + colorLine}></div>
									<div className={colorTitle} dangerouslySetInnerHTML={{ __html: p.more }} />
									{p.category && p.category.map((id: number) => <span
										key={id}
										className={"mr-2 opacity-50" + colorTitle}
									>
										#{getName(id)}
									</span>)}
								</div>
							</div>
						</Link>
					</div>)}
				</div>
			</div>
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
	let url = base + "/api/" + slug
	let allPosts = []
	let allCat = []
	let allPostCat = []
	try {
		let requestPosts = await fetch(url)
		allPosts = await requestPosts.json()
		let requestCat = await fetch(base + "/api/project/all-category")
		allCat = await requestCat.json()
		let requestPostsCat = await fetch(base + "/api/project-category/" + slug)
		allPostCat = await requestPostsCat.json()

	} catch (error) { }
	return {
		props: {
			allPosts,
			allCat,
			allPostCat,
			slug
		},
		revalidate: 10
	}
}