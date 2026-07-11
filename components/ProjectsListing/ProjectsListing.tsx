'use client';

import { useMemo } from 'react';

import type { ProjectsPageContent } from '../../lib/projectsPageDefaults';
import { sortProjectsByDate } from '../../lib/sortProjects';
import type { Category, Projects } from '../../types';
import LatestProjects from '../LatestProjects/LatestProjects';
import { INITIAL_PROJECTS_COUNT } from './constants';
import ProjectsGridSection from './ProjectsGridSection';
import ProjectsListingHero from './ProjectsListingHero';

type ProjectsListingProps = {
	projects: Projects;
	categories: Category[];
	content: ProjectsPageContent;
	locale?: 'en' | 'pt';
};

function projectHref(slug: string, locale: 'en' | 'pt') {
	const prefix = locale === 'pt' ? '/PT' : '';
	return `${prefix}/project/${slug}`.replace(/^\/\//, '/') || `/project/${slug}`;
}

export default function ProjectsListing({ projects, categories, content, locale = 'en' }: ProjectsListingProps) {
	const sorted = useMemo(() => sortProjectsByDate(projects), [projects]);

	if (!sorted.length) {
		return (
			<>
				<ProjectsListingHero content={content} />
				<p className="py-12 text-(--muted)">{content.emptyMessage}</p>
			</>
		);
	}

	return (
		<>
			<ProjectsListingHero content={content} />

			<ProjectsGridSection
				projects={sorted}
				categories={categories}
				locale={locale}
				initialCount={INITIAL_PROJECTS_COUNT}
				hrefForSlug={(slug) => projectHref(slug, locale)}
			/>

			<LatestProjects projects={sorted} locale={locale} />

			<div className="h-10" />
		</>
	);
}
