import React from "react";
import HeaderComponents from "../components/HeaderComponents";
import FooterComponents from "../components/FooterComponents";
import Link from 'next/link'
import Image from 'next/image'

export default function ProjectBySlug({ allPosts, allPostCat, allCat, slug }: any) {

	let dictionary: any = {
		'branding': 'branding',
		'digital-and-internet': 'digital',
		'work': 'home',
		'graphical-arquitecture': 'graphic-architecture',

	}

	function getName(id: any): string {
		return allCat.find((c: any) => c.id == id).slug
	}
	allPostCat = allPostCat.map((post: any) => {
		post.categorySlugs = post?.category?.map((catId: number) => getName(catId)) || []
		return post
	})
	allPostCat = allPostCat.filter((f: any) => f.categorySlugs.includes(dictionary?.[slug] || slug || ''))
	return (
		<div>
			<HeaderComponents />
			<div className="container lg:w-[1200px] mx-auto">
				<h1 className="text-white font-hk my-8">
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
									className="p-8 transition-all duration-300 opacity-0 hover:opacity-100 block absolute z-10 top-0 left-0 bg-[#C00D] w-full h-[1000px]"
								>
									<strong className="text-white font-bold">{p.title}</strong>
									<div className="inline-block w-[40px] h-[1px] mb-[6px] mx-[6px] bg-[#FFF] "></div>
									<div dangerouslySetInnerHTML={{ __html: p.more }} />
									{p.category && p.category.map((id: number) => <span
										key={id}
										className="mr-2 text-[#FFF6]"
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




