'use client';

import Link from 'next/link';

import type { CapabilitiesContent } from '../../lib/capabilitiesDefaults';
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
	const prefix = locale === 'pt' ? '/PT' : '';
	const workHref = `${prefix}/work`.replace(/^\/\//, '/') || '/work';
	const siteUi = useSiteUiLocale(locale);
	const cta = siteUi.labels.seeMoreWork;

	return (
		<article className="capabilities-page">
			<CapabilitiesHero headline={content.headline} />

			<section className="px-7 py-10 md:py-14">
				<CapabilitiesAccordion sections={content.sections} />
			</section>

			<div className="flex justify-center px-7 pb-12 md:pb-16">
				<Link href={workHref} className="selected-projects-cta font-hk">
					{cta}
				</Link>
			</div>

			<LatestProjects projects={latestProjects} locale={locale} />

			<div className="h-10" />
		</article>
	);
}
