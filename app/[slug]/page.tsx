import { cookies } from 'next/headers';
import { notFound } from 'next/navigation';

import { GetApi, GetCategoriesApi, GetIntroApi } from '../../components/ApiWp';
import AboutPage from '../../components/About/AboutPage';
import CapabilitiesPage from '../../components/Capabilities/CapabilitiesPage';
import ContactPage from '../../components/Contact/ContactPage';
import EducationPage from '../../components/Education/EducationPage';
import LegalWpPage from '../../components/LegalWpPage';
import ProjectsListing from '../../components/ProjectsListing/ProjectsListing';
import { fetchAboutPage } from '../../lib/fetchAboutPage';
import { fetchCapabilitiesPage } from '../../lib/fetchCapabilitiesPage';
import { fetchContactPage } from '../../lib/fetchContactPage';
import { fetchEducationPage } from '../../lib/fetchEducationPage';
import { fetchLegalPage, isLegalPageSlug } from '../../lib/fetchLegalPage';
import { getBaseUrl } from '../../lib/getBaseUrl';
import type { Category, Intro, Projects } from '../../types';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

async function fetchWorkPage(locale: 'en' | 'pt') {
	if (locale === 'en') {
		const [projects, categories, intro] = await Promise.all([
			GetApi('/project/', { _embed: '', per_page: 100 }),
			GetCategoriesApi('/project-category', { per_page: 22 }),
			GetIntroApi(),
		]).catch((error) => {
			console.error('Error fetching work page', error);
			return [[], [], null] as [Projects, Category[], Intro | null];
		});

		return { projects, categories, intro };
	}

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

export default async function SlugPage({ params }: PageProps) {
	const { slug } = await params;

	if (slug === 'work') {
		const lang = (await cookies()).get('language')?.value ?? '';
		const locale = lang === 'PT' ? 'pt' : 'en';
		const { projects, categories, intro } = await fetchWorkPage(locale);

		return <ProjectsListing projects={projects} categories={categories} intro={intro} locale={locale} />;
	}

	if (slug === 'education') {
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

	if (slug === 'capacidades') {
		const lang = (await cookies()).get('language')?.value ?? '';
		const locale = lang === 'PT' ? 'pt' : 'en';
		const { content, latestProjects } = await fetchCapabilitiesPage(locale);

		return <CapabilitiesPage content={content} latestProjects={latestProjects} locale={locale} />;
	}

	if (slug === 'about') {
		const lang = (await cookies()).get('language')?.value ?? '';
		const locale = lang === 'PT' ? 'pt' : 'en';
		const { content, latestProjects } = await fetchAboutPage(locale);

		return <AboutPage content={content} latestProjects={latestProjects} locale={locale} />;
	}

	if (slug === 'contact-3') {
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
