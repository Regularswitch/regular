'use client';

import type { CapabilitiesContent } from '../../lib/content/capabilities/defaults';
import type { Projects } from '../../types';
import LatestProjects from '../LatestProjects/LatestProjects';
import CapabilitiesAccordion from './CapabilitiesAccordion';
import CapabilitiesFaq from './CapabilitiesFaq';
import CapabilitiesHero from './CapabilitiesHero';

type CapabilitiesPageProps = {
	content: CapabilitiesContent;
	latestProjects: Projects;
	locale?: 'en' | 'pt';
};

export default function CapabilitiesPage({ content, latestProjects, locale = 'en' }: CapabilitiesPageProps) {
	return (
		<article className="capabilities-page">
			<CapabilitiesHero headline={content.headline} />

			<section className="pb-16 md:pb-28">
				<CapabilitiesAccordion sections={content.sections} locale={locale} />
			</section>
			
			<div className="pb-16 md:pb-28">
				<CapabilitiesFaq title={content.faqTitle} items={content.faq} locale={locale} />
			</div>

			<div className="pt-4 md:pt-8">
				<LatestProjects projects={latestProjects} locale={locale} />
			</div>

			<div className="h-10" />
		</article>
	);
}
