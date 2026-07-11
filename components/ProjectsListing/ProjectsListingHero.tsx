import type { ProjectsPageContent } from '../../lib/projectsPageDefaults';
import { SectionHeadingArrow } from '../SiteIcons';

type ProjectsListingHeroProps = {
	content: ProjectsPageContent;
};

export default function ProjectsListingHero({ content }: ProjectsListingHeroProps) {
	return (
		<section className="projects-listing-hero py-12 md:py-20" aria-label={content.title}>
			<h1 className="inline-flex items-center gap-1.5 text-base font-medium text-(--fg) md:text-lg">
				{content.title}
				<SectionHeadingArrow />
			</h1>

			<div
				className="intro-headline mt-8 font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-medium leading-[1.05] tracking-[-0.02em] md:mt-10"
				dangerouslySetInnerHTML={{ __html: content.headline }}
			/>
		</section>
	);
}
