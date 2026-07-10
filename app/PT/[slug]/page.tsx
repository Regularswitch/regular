import { notFound } from 'next/navigation';

import AboutPage from '../../../components/About/AboutPage';
import CapabilitiesPage from '../../../components/Capabilities/CapabilitiesPage';
import ContactPage from '../../../components/Contact/ContactPage';
import EducationPage from '../../../components/Education/EducationPage';
import LegalWpPage from '../../../components/LegalWpPage';
import { GetIntroApi } from '../../../components/ApiWp';
import ProjectsListing from '../../../components/ProjectsListing/ProjectsListing';
import { fetchAboutPage } from '../../../lib/fetchAboutPage';
import { fetchCapabilitiesPage } from '../../../lib/fetchCapabilitiesPage';
import { fetchContactPage } from '../../../lib/fetchContactPage';
import { fetchEducationPage } from '../../../lib/fetchEducationPage';
import { fetchLegalPage, isLegalPageSlug } from '../../../lib/fetchLegalPage';
import { getBaseUrl } from '../../../lib/getBaseUrl';
import type { Category, Intro, Projects } from '../../../types';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

async function fetchPtWorkPage() {
	const base = getBaseUrl();
	const headers = { Cookie: 'language=PT' };

	const [projects, categories, intro] = await Promise.all([
		fetch(`${base}/api/project`, { headers }).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`, { headers }).then((r) => r.json() as Promise<Category[]>),
		GetIntroApi({ translate: 'PT' }),
	]).catch((error) => {
		console.error('Error fetching PT work page', error);
		return [[], [], null] as [Projects, Category[], Intro | null];
	});

	return { projects, categories, intro };
}

export default async function PtSlugPage({ params }: PageProps) {
	const { slug } = await params;

	if (slug === 'work') {
		const { projects, categories, intro } = await fetchPtWorkPage();
		return <ProjectsListing projects={projects} categories={categories} intro={intro} locale="pt" />;
	}

	if (slug === 'education') {
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

	if (slug === 'capacidades') {
		const { content, latestProjects } = await fetchCapabilitiesPage('pt');

		return <CapabilitiesPage content={content} latestProjects={latestProjects} locale="pt" />;
	}

	if (slug === 'about') {
		const { content, latestProjects } = await fetchAboutPage('pt');

		return <AboutPage content={content} latestProjects={latestProjects} locale="pt" />;
	}

	if (slug === 'contact-3') {
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
