import React from "react";
import HeaderComponents from "../components/HeaderComponents";
import FooterComponents from "../components/FooterComponents";
import Image from 'next/image'
import FontMagic from "../components/FontMagic";
import FontVariante from "../components/FontVariante";
import Link from 'next/link'




function Home({ posts = [], cats = [] }: any) {
	function getName(id: any) {
		return cats.find((c: any) => c.id == id).title
	}

	return (
		<div>
			<HeaderComponents />
			<FontVariante text="REGULAR" text2="SWITCH" />
			<FontVariante text="SWITCH" />
			<section className="text-white container mx-auto text-[20px] lg:text-[50px] font-hk leading-[1em] font-extrabold py-4 px-4 lg:py-[150px]">
				<h2 className="block mb-[40px]">Branding / Digital / Arquitetura Gráfica</h2>
				<p>
					Regularswitch é uma agência de design multi-cultural
					com escritório no Brasil e na França. Trabalhando na
					fronteira entre analógico e digital para oferecer experiências
					visuais que importam.
				</p>
			</section>

			<div className="container mx-auto p-4">
				<div className="columns-1 md:columns-2 gap-4">
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
										<img className="w-full transition-all  duration-300 hover:scale-[1.05]" src={p.image_full} alt={p.title} />
									</div>
									<div>
										<strong className="text-white inline-block mt-4">{p.title}</strong>
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



export default function Index({ allPosts, allCat }: any) {
	return (
		<div>
			<Home posts={allPosts} cats={allCat}></Home>
		</div>
	);
}

export async function getStaticProps() {
	let base = process.env?.BASE
	let url = base + "/api/project"
	let allPosts = []
	let allCat = []
	try {
		let requestPosts = await fetch(url)
		allPosts = await requestPosts.json()
		let requestCat = await fetch(base + "/api/project/all-category")
		allCat = await requestCat.json()
	} catch (error) { }

	return {
		props: {
			allPosts,
			allCat
		},
		revalidate: 10
	}
}