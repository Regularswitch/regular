import { notFound } from 'next/navigation';

import AboutPage from '../../../components/About/AboutPage';
import CapabilitiesPage from '../../../components/Capabilities/CapabilitiesPage';
import ContactPage from '../../../components/Contact/ContactPage';
import EducationPage from '../../../components/Education/EducationPage';
import LegalWpPage from '../../../components/LegalWpPage';
import ProjectsListing from '../../../components/ProjectsListing/ProjectsListing';
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

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

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

		return <CapabilitiesPage content={content} latestProjects={latestProjects} locale="pt" />;
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
