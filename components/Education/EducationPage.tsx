'use client';

import type { Category, Projects } from '../../types';
import type { EducationContent } from '../../lib/educationDefaults';
import { projectHref } from '../../lib/filterEducationProjects';
import LatestProjects from '../LatestProjects/LatestProjects';
import MediaCarousel from '../MediaCarousel/MediaCarousel';
import ProjectAccordion from '../Project/ProjectAccordion';
import ProjectsGridSection from '../ProjectsListing/ProjectsGridSection';
import EducationHero from './EducationHero';

const EDUCATION_INITIAL_PROJECTS = 2;

type EducationPageProps = {
	content: EducationContent;
	projects: Projects;
	categories: Category[];
	locale?: 'en' | 'pt';
};

export default function EducationPage({
	content,
	projects,
	categories,
	locale = 'en',
}: EducationPageProps) {
	return (
		<article className="education-page">
			<EducationHero
				image={content.heroImage}
				video={content.heroVideo}
				headline={content.headline}
			/>

			<section className="education-studio py-10 md:py-14">
				<div className="grid items-start gap-8 md:grid-cols-2 md:gap-12 lg:gap-16">
					<div className="education-studio-copy">
						<p className="font-hk text-sm uppercase tracking-[0.18em] text-(--muted)">
							{locale === 'pt' ? 'Estúdio' : 'Studio'}
						</p>
						<p className="mt-3 font-hk text-base leading-relaxed text-(--muted) md:text-lg">
							{locale === 'pt'
								? 'Espaços de troca, workshops e colaborações entre escolas.'
								: 'Spaces for exchange, workshops and school collaborations.'}
						</p>
					</div>

					<MediaCarousel
						images={content.studioImages ?? []}
						locale={locale}
						autoplayMs={5000}
						ariaLabel={locale === 'pt' ? 'Fotos do estúdio' : 'Studio photos'}
					/>
				</div>
			</section>

			<section className="py-10 md:py-14">
				<ProjectAccordion sections={content.accordionSections} titleCase="sentence" />
			</section>

			<ProjectsGridSection
				projects={projects}
				categories={categories}
				locale={locale}
				initialCount={EDUCATION_INITIAL_PROJECTS}
				hrefForSlug={(slug) => projectHref(slug, locale)}
				className="selected-projects pb-12 md:pb-20"
			/>

			<LatestProjects projects={projects} locale={locale} />

			<div className="h-10" />
		</article>
	);
}
