'use client';

import Link from 'next/link';

import { PROJECTS_PAGE_SLUG, pagePath } from '../../lib/site/pageSlugs';
import { withLocalePrefix } from '../../lib/site/resolveSiteUi';
import type { AboutContent } from '../../lib/content/about/defaults';
import type { Projects } from '../../types';
import LatestProjects from '../LatestProjects/LatestProjects';
import { useSiteUiLocale } from '../SiteUi/SiteUiProvider';
import AboutAccordionPanel from './AboutAccordionPanel';
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

	return (
		<article className="about-page">
			<AboutHero image={content.heroImage} video={content.heroVideo} />

			<section className="about-intro py-10 md:grid md:grid-cols-2 md:items-start md:gap-12 md:py-14 lg:gap-16">
				<div
					className="intro-headline min-w-0 font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-medium leading-[1.05] tracking-[-0.02em]"
					dangerouslySetInnerHTML={{ __html: content.headline }}
				/>

				<div
					className="about-body intro-body mt-8 min-w-0 max-w-none font-hk text-base leading-relaxed md:mt-0 md:text-lg"
					dangerouslySetInnerHTML={{ __html: content.body }}
				/>
			</section>

			<div className="pb-10 md:pb-14">
				<AboutAccordionPanel sections={content.accordionSections} />
			</div>

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
