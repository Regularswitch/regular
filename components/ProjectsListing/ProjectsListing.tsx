'use client';

import { useMemo } from 'react';

import type { ProjectsPageContent } from '../../lib/content/projects-page/defaults';
import { sortProjectsByDate } from '../../lib/projects/sort';
import type { Category, Projects } from '../../types';
import LatestProjects from '../LatestProjects/LatestProjects';
import { useSiteUiLayout } from '../SiteUi/SiteUiProvider';
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
	const layout = useSiteUiLayout();
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
				initialCount={layout.projectsInitialCount}
				hrefForSlug={(slug) => projectHref(slug, locale)}
			/>

			<LatestProjects projects={sorted} locale={locale} />

			<div className="h-10" />
		</>
	);
}
