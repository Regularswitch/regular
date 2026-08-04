'use client';

import type { Category, Projects } from '../../types';
import type { EducationContent } from '../../lib/educationDefaults';
import LatestProjects from '../LatestProjects/LatestProjects';
import MediaCarousel from '../MediaCarousel/MediaCarousel';
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
	const studioImages = content.studioImages ?? [];

	return (
		<article className="education-page">
			<EducationHero
				image={content.heroImage}
				video={content.heroVideo}
				headline={content.headline}
			/>

			<section className="py-10 md:py-14">
				<ProjectAccordion sections={content.accordionSections} />
			</section>

			{studioImages.length > 0 ? (
				<section
					className="education-studio py-6 md:py-10"
					aria-label={locale === 'pt' ? 'Estúdio' : 'Studio'}
				>
					<div className="mx-auto max-w-3xl">
						<MediaCarousel
							images={studioImages}
							locale={locale}
							autoplayMs={4500}
							ariaLabel={locale === 'pt' ? 'Fotos do estúdio' : 'Studio photos'}
						/>
					</div>
				</section>
			) : null}

			{institutions.length > 0 ? (
				<section
					className="education-institutions space-y-16 py-6 md:space-y-24 md:py-10"
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
