import type { Intro } from '../../types';
import { DEFAULT_INTRO_EN, DEFAULT_INTRO_PT } from '../Intro/IntroSection';

type ProjectsListingHeroProps = {
	intro: Intro | null;
	locale?: 'en' | 'pt';
};

export default function ProjectsListingHero({ intro, locale = 'en' }: ProjectsListingHeroProps) {
	const fallback = locale === 'pt' ? DEFAULT_INTRO_PT : DEFAULT_INTRO_EN;
	const { headline } = intro ?? fallback;
	const title = locale === 'pt' ? 'Projetos selecionados' : 'Selected projects';

	return (
		<section className="projects-listing-hero px-7 pt-12 md:pt-20" aria-label={title}>
			<h1 className="text-base font-medium text-(--fg) md:text-lg">
				{title} <span aria-hidden>↘</span>
			</h1>

			<div
				className="intro-headline mt-8 font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-extrabold leading-[1.05] tracking-[-0.02em] md:mt-10"
				dangerouslySetInnerHTML={{ __html: headline }}
			/>
		</section>
	);
}
