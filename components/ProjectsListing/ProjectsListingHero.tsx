import type { ProjectsPageContent } from '../../lib/content/projects-page/defaults';
import { SectionHeadingArrow } from '../SiteIcons';

type ProjectsListingHeroProps = {
	content: ProjectsPageContent;
};

export default function ProjectsListingHero({ content }: ProjectsListingHeroProps) {
	return (
		<section className="projects-listing-hero py-10 md:py-16" aria-label={content.title}>
			<p className="inline-flex items-center gap-1.5 text-xl font-medium text-(--fg)">
				{content.title}
				<SectionHeadingArrow />
			</p>

			<h1
				className="intro-headline mt-6 max-w-5xl font-hk md:mt-8"
				dangerouslySetInnerHTML={{ __html: content.headline }}
			/>
		</section>
	);
}
