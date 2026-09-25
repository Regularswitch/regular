'use client';

import Link from 'next/link';

import { PROJECTS_PAGE_SLUG, pagePath } from '../../lib/site/pageSlugs';
import { withLocalePrefix } from '../../lib/site/resolveSiteUi';
import type { AboutContent } from '../../lib/content/about/defaults';
import type { Projects } from '../../types';
import LatestProjects from '../LatestProjects/LatestProjects';
import ProjectAccordion from '../Project/ProjectAccordion';
import ProjectGallery from '../Project/ProjectGallery';
import { useSiteUiLocale } from '../SiteUi/SiteUiProvider';
import AboutHero from './AboutHero';

type AboutPageProps = {
	content: AboutContent;
	latestProjects: Projects;
	locale?: 'en' | 'pt';
};

export default function AboutPage({ content, latestProjects, locale = 'en' }: AboutPageProps) {
	const projectsHref = withLocalePrefix(pagePath(PROJECTS_PAGE_SLUG), locale);
	const siteUi = useSiteUiLocale(locale);
	const cta = siteUi.labels.seeMoreWork;
	const galleryLabel = locale === 'pt' ? 'Galeria' : 'Gallery';
	const hasIntro = Boolean(content.headline?.trim() || content.body?.trim());
	const hasAccordion = content.accordionSections.length > 0;
	const gallery = content.gallery ?? [];

	return (
		<article className="about-page">
			<AboutHero image={content.heroImage} video={content.heroVideo} />

			{hasIntro || hasAccordion ? (
				<section className="about-intro py-10 md:grid md:grid-cols-2 md:items-start md:gap-12 md:py-14 lg:gap-16">
					{hasIntro ? (
						<div className="min-w-0">
							{content.headline?.trim() ? (
								<h1
									className="intro-headline font-hk"
									dangerouslySetInnerHTML={{ __html: content.headline }}
								/>
							) : null}
							{content.body?.trim() ? (
								<div
									className={`about-body intro-body min-w-0 max-w-none font-hk${
										content.headline?.trim() ? ' mt-8' : ''
									}`}
									dangerouslySetInnerHTML={{ __html: content.body }}
								/>
							) : null}
						</div>
					) : (
						<div className="hidden min-w-0 md:block" aria-hidden />
					)}

					{hasAccordion ? (
						<div className="about-accordion-col mt-8 min-w-0 md:mt-0">
							<ProjectAccordion sections={content.accordionSections} />
						</div>
					) : null}
				</section>
			) : null}

			{gallery.length > 0 ? (
				<div className="pb-10 md:pb-14">
					<ProjectGallery images={gallery} title={galleryLabel} locale={locale} />
				</div>
			) : null}

			<div className="flex justify-center pb-12 md:pb-16">
				<Link href={projectsHref} className="selected-projects-cta font-hk">
					{cta}
				</Link>
			</div>

			<LatestProjects projects={latestProjects} locale={locale} />

			<div className="h-10" />
		</article>
	);
}
