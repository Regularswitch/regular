import type { ProjectsPageContent } from '../../lib/content/projects-page/defaults';
import { SectionHeadingArrow } from '../SiteIcons';

type ProjectsListingHeroProps = {
	content: ProjectsPageContent;
};

export default function ProjectsListingHero({ content }: ProjectsListingHeroProps) {
	return (
		<section className="projects-listing-hero py-10 md:py-16" aria-label={content.title}>
			<h1 className="inline-flex items-center gap-1.5 text-base font-medium text-(--fg) md:text-lg">
				{content.title}
				<SectionHeadingArrow />
			</h1>

			<div
				className="intro-headline mt-6 max-w-5xl font-hk text-[clamp(1.5rem,4.2vw,2.75rem)] font-medium leading-[1.08] tracking-[-0.02em] md:mt-8"
				dangerouslySetInnerHTML={{ __html: content.headline }}
			/>
		</section>
	);
}
