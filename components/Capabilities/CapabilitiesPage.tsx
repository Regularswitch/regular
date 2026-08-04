'use client';

import Link from 'next/link';

import { PROJECTS_PAGE_SLUG, pagePath } from '../../lib/site/pageSlugs';
import { withLocalePrefix } from '../../lib/site/resolveSiteUi';
import type { CapabilitiesContent } from '../../lib/content/capabilities/defaults';
import type { Projects } from '../../types';
import LatestProjects from '../LatestProjects/LatestProjects';
import { useSiteUiLocale } from '../SiteUi/SiteUiProvider';
import CapabilitiesAccordion from './CapabilitiesAccordion';
import CapabilitiesHero from './CapabilitiesHero';

type CapabilitiesPageProps = {
	content: CapabilitiesContent;
	latestProjects: Projects;
	locale?: 'en' | 'pt';
};

export default function CapabilitiesPage({ content, latestProjects, locale = 'en' }: CapabilitiesPageProps) {
	const projectsHref = withLocalePrefix(pagePath(PROJECTS_PAGE_SLUG), locale);
	const siteUi = useSiteUiLocale(locale);
	const cta = siteUi.labels.seeMoreWork;

	return (
		<article className="capabilities-page">
			<CapabilitiesHero headline={content.headline} />

			<section className="py-10 md:py-14">
				<CapabilitiesAccordion sections={content.sections} />
			</section>

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
