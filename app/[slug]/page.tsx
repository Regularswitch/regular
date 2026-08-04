import { cookies } from 'next/headers';
import { notFound } from 'next/navigation';

import AboutPage from '../../components/About/AboutPage';
import CapabilitiesPage from '../../components/Capabilities/CapabilitiesPage';
import ContactPage from '../../components/Contact/ContactPage';
import EducationPage from '../../components/Education/EducationPage';
import LegalWpPage from '../../components/LegalWpPage';
import ProjectsListing from '../../components/ProjectsListing/ProjectsListing';
import {
	ABOUT_PAGE_SLUG,
	CAPABILITIES_PAGE_SLUG,
	CONTACT_PAGE_SLUG,
	EDUCATION_PAGE_SLUG,
	isLegalPageSlug,
	isProjectsPageSlug,
} from '../../lib/site/pageSlugs';
import { fetchAboutPage } from '../../lib/fetch/about';
import { fetchCapabilitiesPage } from '../../lib/fetch/capabilities';
import { fetchContactPage } from '../../lib/fetch/contact';
import { fetchEducationPage } from '../../lib/fetch/education';
import { fetchLegalPage } from '../../lib/fetch/legal';
import { fetchProjectsListingPage } from '../../lib/fetch/projectsListing';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

async function fetchProjectsPage(locale: 'en' | 'pt') {
	return fetchProjectsListingPage(locale);
}

export default async function SlugPage({ params }: PageProps) {
	const { slug } = await params;

	if (isProjectsPageSlug(slug)) {
		const lang = (await cookies()).get('language')?.value ?? '';
		const locale = lang === 'PT' ? 'pt' : 'en';
		const { projects, categories, content } = await fetchProjectsPage(locale);

		return <ProjectsListing projects={projects} categories={categories} content={content} locale={locale} />;
	}

	if (slug === EDUCATION_PAGE_SLUG) {
		const lang = (await cookies()).get('language')?.value ?? '';
		const locale = lang === 'PT' ? 'pt' : 'en';
		const { content, projects, categories } = await fetchEducationPage(locale);

		return (
			<EducationPage
				content={content}
				projects={projects}
				categories={categories}
				locale={locale}
			/>
		);
	}

	if (slug === CAPABILITIES_PAGE_SLUG) {
		const lang = (await cookies()).get('language')?.value ?? '';
		const locale = lang === 'PT' ? 'pt' : 'en';
		const { content, latestProjects } = await fetchCapabilitiesPage(locale);

		return <CapabilitiesPage content={content} latestProjects={latestProjects} locale={locale} />;
	}

	if (slug === ABOUT_PAGE_SLUG) {
		const lang = (await cookies()).get('language')?.value ?? '';
		const locale = lang === 'PT' ? 'pt' : 'en';
		const { content, latestProjects } = await fetchAboutPage(locale);

		return <AboutPage content={content} latestProjects={latestProjects} locale={locale} />;
	}

	if (slug === CONTACT_PAGE_SLUG) {
		const lang = (await cookies()).get('language')?.value ?? '';
		const locale = lang === 'PT' ? 'pt' : 'en';
		const { content } = await fetchContactPage(locale);

		return <ContactPage content={content} locale={locale} />;
	}

	if (isLegalPageSlug(slug)) {
		const lang = (await cookies()).get('language')?.value ?? '';
		const locale = lang === 'PT' ? 'pt' : 'en';
		const page = await fetchLegalPage(slug, locale).catch((error) => {
			console.error('Error fetching legal page', error);
			return null;
		});

		if (!page?.content) notFound();

		return <LegalWpPage title={page.title} content={page.content} />;
	}

	notFound();
}
