'use client';

import type { Category, Projects } from '../../types';
import type { EducationContent } from '../../lib/content/education/defaults';
import LatestProjects from '../LatestProjects/LatestProjects';
import ProjectAccordion from '../Project/ProjectAccordion';
import EducationHero from './EducationHero';
import EducationInstitutionBlock from './EducationInstitutionBlock';

type EducationPageProps = {
	content: EducationContent;
	projects: Projects;
	categories: Category[];
	locale?: 'en' | 'pt';
};

export default function EducationPage({
	content,
	projects,
	locale = 'en',
}: EducationPageProps) {
	const institutions = content.institutions ?? [];

	return (
		<article className="education-page">
			<EducationHero image={content.heroImage} video={content.heroVideo} />

			{content.headline?.trim() || content.accordionSections.length > 0 ? (
				<section className="education-intro-accordion py-10 md:grid md:grid-cols-2 md:items-start md:gap-12 md:py-14 lg:gap-16">
					<div className="min-w-0">
						{content.headline?.trim() ? (
							<h1
								className="intro-headline font-hk"
								dangerouslySetInnerHTML={{ __html: content.headline }}
							/>
						) : null}
					</div>

					{content.accordionSections.length > 0 ? (
						<div className="education-accordion-col mt-10 min-w-0 md:mt-0">
							<ProjectAccordion sections={content.accordionSections} />
						</div>
					) : null}
				</section>
			) : null}

			{institutions.length > 0 ? (
				<section
					className="education-institutions space-y-20 py-8 md:space-y-28 md:py-12"
					aria-label={locale === 'pt' ? 'Instituições' : 'Institutions'}
				>
					{institutions.map((institution) => (
						<EducationInstitutionBlock key={institution.name} institution={institution} />
					))}
				</section>
			) : null}

			<LatestProjects projects={projects} locale={locale} />

			<div className="h-10" />
		</article>
	);
}
