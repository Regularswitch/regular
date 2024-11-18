import React, { FC } from 'react';
import HeaderComponents from '../components/HeaderComponents';
import FooterComponents from '../components/FooterComponents';
import FontVariante from '../components/FontVariante';
import { HomeProps, IndexProps } from '../types';
import ContainerProjects from '../components/ContainerProjects';

const Home: FC<HomeProps> = ({ projects, cats, allMetas }) => {
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
				<ContainerProjects projects={projects} cats={cats} allMetas={allMetas} />
			</div>
			<div className="h-10" />
			<FooterComponents />
		</div>
	);
};

const Index: FC<IndexProps> = ({ projects, allCat, allMetas }) => {
	return (
		<div>
			<Home projects={projects} cats={allCat} allMetas={allMetas} />
		</div>
	);
};

export const getStaticProps = async () => {
	const base = process.env?.BASE;
	const url = `${base}/api/project`;
	let projects = [];
	let allCat = [];
	let allMetas = [];

	try {
		projects = await (await fetch(url)).json();
		allCat = await (await fetch(`${base}/api/project/all-category`)).json();
		allMetas = await (await fetch(`${base}/api/project/all-metas`)).json();
	} catch (error) {
		console.error('Failed to fetch data:', error);
	}

	return {
		props: {
			projects,
			allCat,
			allMetas,
		},
		revalidate: 600,
	};
};

export default Index;
