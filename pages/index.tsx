import React from "react";
import HeaderComponents from "../components/HeaderComponents";
import FooterComponents from "../components/FooterComponents";
import Image from 'next/image'
import FontMagic from "../components/FontMagic";
import FontVariante from "../components/FontVariante";
import Link from 'next/link'




function Home({ posts = [], cats = [], allMetas = [] }: any) {
	function getName(id: any) {
		return cats.find((c: any) => c.id == id).title
	}

	function get_image_secondary_by_slug(slug:any) {
		return (allMetas.find( (p:any) => slug == p.slug ))?.img_secondary?.url
	}
	

	return (
		<div>
			<HeaderComponents />
			<FontVariante text="REGULAR" text2="SWITCH" />
			<div className="mt-[-40px]">
				<FontVariante text="SWITCH" />
			</div>
			<section className="text-black container mx-auto text-[20px] lg:text-[50px] font-hk leading-[1em] font-extrabold py-4 px-4 lg:py-[150px]">
				<h2 className="block mb-[40px]">Branding / Digital / Graphic Architecture</h2>
				<p>
					RegularSwitch is a multi-cultural design agency based in Brazil. 
					Working on the edge between analog and digital to offer visual 
					experiences that matter.
				</p>
			</section>

			<div className="mx-auto p-4 lg:w-[90vw]">
				<div className="columns-1 md:columns-3 gap-4">
					{posts.filter((f: any) => f.category.includes(17)).map((p: any) => (
						<div className="break-inside-avoid pb-4" key={p.id}>
							<Link href={'project/' + p.slug}  >
								<div className="font-hk">
									<div className="block relative w-full overflow-hidden">
										{/* <Image
										alt={p.title}
										src={
											p.image_full
										}
										layout='fill'										
										objectFit='cover'
									/> */}
										{
											get_image_secondary_by_slug(p.slug) &&
											<img className="absolute top-0 left-0 w-full transition-all  duration-300 hover:scale-[1.05] opacity-0 hover:opacity-100" src={get_image_secondary_by_slug(p.slug)} alt={p.title} />
										}
										<img className="w-full transition-all  duration-300 hover:scale-[1.05]" src={p.image_full} alt={p.title} />
									</div>
									<div>
										<strong className="text-black inline-block mt-4">{p.title}</strong>
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
						</div>
					))}
				</div>
			</div>
			<div className="h-96"></div>
			<FooterComponents />
		</div>
	);
}



export default function Index({ allPosts, allCat, allMetas }: any) {
	return (
		<div>
			<Home posts={allPosts} cats={allCat} allMetas={allMetas}></Home>
		</div>
	);
}

export async function getStaticProps() {
	let base = process.env?.BASE
	let url = base + "/api/project"
	let allPosts = []
	let allCat = []
	let allMetas = []
	try {
		let requestPosts = await fetch(url)
		allPosts = await requestPosts.json()
		let requestCat = await fetch(base + "/api/project/all-category")
		allCat = await requestCat.json()
		allMetas = await (await fetch(base +"/api/project/all-metas")).json()
		
	} catch (error) { }

	return {
		props: {
			allPosts,
			allCat,
			allMetas
		},
		revalidate: 10
	}
}