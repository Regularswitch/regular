import { notFound } from 'next/navigation';
import type { Metadata } from 'next';

import AboutPage from '../../../components/About/AboutPage';
import CapabilitiesPage from '../../../components/Capabilities/CapabilitiesPage';
import ContactPage from '../../../components/Contact/ContactPage';
import EducationPage from '../../../components/Education/EducationPage';
import LegalWpPage from '../../../components/LegalWpPage';
import ProjectsListing from '../../../components/ProjectsListing/ProjectsListing';
import JsonLd from '../../../components/Seo/JsonLd';
import {
	ABOUT_PAGE_SLUG,
	CAPABILITIES_PAGE_SLUG,
	CONTACT_PAGE_SLUG,
	EDUCATION_PAGE_SLUG,
	isLegalPageSlug,
	isProjectsPageSlug,
} from '../../../lib/site/pageSlugs';
import { fetchAboutPage } from '../../../lib/fetch/about';
import { fetchCapabilitiesPage } from '../../../lib/fetch/capabilities';
import { fetchContactPage } from '../../../lib/fetch/contact';
import { fetchEducationPage } from '../../../lib/fetch/education';
import { fetchLegalPage } from '../../../lib/fetch/legal';
import { fetchProjectsListingPage } from '../../../lib/fetch/projectsListing';
import { fetchSectionSeo, sectionSeoFallbacks } from '../../../lib/seo/fetch';
import { buildPageMetadata } from '../../../lib/seo/metadata';
import { seoPostTypeForRouteSlug } from '../../../lib/seo/routeMap';
import { buildFaqPageJsonLd } from '../../../lib/seo/schema';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
	const { slug } = await params;
	const section = seoPostTypeForRouteSlug(slug);
	if (!section) {
		return buildPageMetadata({}, { fallbackTitle: 'RegularSwitch', locale: 'pt' });
	}

	const seo = await fetchSectionSeo(section, 'pt');
	const fallback = sectionSeoFallbacks(section, 'pt');

	return buildPageMetadata(seo, {
		fallbackTitle: fallback.title,
		fallbackDescription: fallback.description,
		locale: 'pt',
		path: `/PT/${slug}`,
	});
}

export default async function PtSlugPage({ params }: PageProps) {
	const { slug } = await params;

	if (isProjectsPageSlug(slug)) {
		const { projects, categories, content } = await fetchProjectsListingPage('pt');
		return <ProjectsListing projects={projects} categories={categories} content={content} locale="pt" />;
	}

	if (slug === EDUCATION_PAGE_SLUG) {
		const { content, projects, categories } = await fetchEducationPage('pt');

		return (
			<EducationPage
				content={content}
				projects={projects}
				categories={categories}
				locale="pt"
			/>
		);
	}

	if (slug === CAPABILITIES_PAGE_SLUG) {
		const { content, latestProjects } = await fetchCapabilitiesPage('pt');
		const faqJsonLd = buildFaqPageJsonLd(content.faq, '/PT/capabilities');

		return (
			<>
				<JsonLd id="capabilities-faq-jsonld" data={faqJsonLd} />
				<CapabilitiesPage content={content} latestProjects={latestProjects} locale="pt" />
			</>
		);
	}

	if (slug === ABOUT_PAGE_SLUG) {
		const { content, latestProjects } = await fetchAboutPage('pt');

		return <AboutPage content={content} latestProjects={latestProjects} locale="pt" />;
	}

	if (slug === CONTACT_PAGE_SLUG) {
		const { content } = await fetchContactPage('pt');

		return <ContactPage content={content} locale="pt" />;
	}

	if (isLegalPageSlug(slug)) {
		const page = await fetchLegalPage(slug, 'pt').catch((error) => {
			console.error('Error fetching PT legal page', error);
			return null;
		});

		if (!page?.content) notFound();

		return <LegalWpPage title={page.title} content={page.content} />;
	}

	notFound();
}
