'use client';

import type { Category, Projects } from '../../types';
import ProjectsGridSection from '../ProjectsListing/ProjectsGridSection';

type CategoryArchivePageProps = {
	category: Category;
	projects: Projects;
	categories: Category[];
	locale?: 'en' | 'pt';
	initialCount: number;
};

function projectHref(slug: string, locale: 'en' | 'pt') {
	const prefix = locale === 'pt' ? '/PT' : '';
	return `${prefix}/project/${slug}`.replace(/^\/\//, '/') || `/project/${slug}`;
}

export default function CategoryArchivePage({
	category,
	projects,
	categories,
	locale = 'en',
	initialCount,
}: CategoryArchivePageProps) {
	const h1 = category.h1?.trim() || category.title;
	const intro = category.intro?.trim();
	const emptyMessage =
		locale === 'pt' ? 'Nenhum projeto nesta tag ainda.' : 'No projects in this tag yet.';

	return (
		<article className="category-archive-page">
			<section className="category-archive-hero py-10 md:py-16" aria-label={h1}>
				<h1 className="intro-headline max-w-5xl font-hk text-[clamp(1.5rem,4.2vw,2.75rem)] font-medium leading-[1.08] tracking-[-0.02em]">
					{h1}
				</h1>
				{intro ? (
					<div
						className="category-archive-intro intro-body mt-6 max-w-3xl font-hk md:mt-8"
						dangerouslySetInnerHTML={{ __html: intro.includes('<') ? intro : `<p>${intro}</p>` }}
					/>
				) : null}
			</section>

			{projects.length > 0 ? (
				<ProjectsGridSection
					projects={projects}
					categories={categories}
					locale={locale}
					initialCount={initialCount}
					hrefForSlug={(slug) => projectHref(slug, locale)}
					ariaLabel={h1}
				/>
			) : (
				<p className="pb-16 text-(--muted)">{emptyMessage}</p>
			)}
		</article>
	);
}
