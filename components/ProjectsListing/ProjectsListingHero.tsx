import type { ProjectsPageContent } from '../../lib/projectsPageDefaults';

type ProjectsListingHeroProps = {
	content: ProjectsPageContent;
};

export default function ProjectsListingHero({ content }: ProjectsListingHeroProps) {
	return (
		<section className="projects-listing-hero px-7 pt-12 md:pt-20" aria-label={content.title}>
			<h1 className="text-base font-medium text-(--fg) md:text-lg">
				{content.title} <span aria-hidden>↘</span>
			</h1>

			<div
				className="intro-headline mt-8 font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-extrabold leading-[1.05] tracking-[-0.02em] md:mt-10"
				dangerouslySetInnerHTML={{ __html: content.headline }}
			/>
		</section>
	);
}
