'use client';

import type { Category, Projects } from '../../types';
import type { EducationContent } from '../../lib/educationDefaults';
import { projectHref } from '../../lib/filterEducationProjects';
import LatestProjects from '../LatestProjects/LatestProjects';
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
			<EducationHero image={content.heroImage} headline={content.headline} />

			<section className="px-7 py-10 md:py-14">
				<ProjectAccordion sections={content.accordionSections} />
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
