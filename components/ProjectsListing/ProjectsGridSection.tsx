'use client';

import Link from 'next/link';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import { CONTACT_PAGE_SLUG, pagePath } from '../../lib/site/pageSlugs';
import { withLocalePrefix } from '../../lib/site/resolveSiteUi';
import type { Category, Projects } from '../../types';
import { getGridSpan, PROJECTS_BATCH_SIZE } from './constants';
import ProjectGridCard from './ProjectGridCard';

type ProjectsGridSectionProps = {
	projects: Projects;
	categories: Category[];
	locale?: 'en' | 'pt';
	initialCount: number;
	hrefForSlug: (slug: string) => string;
	ariaLabel?: string;
	className?: string;
};

export default function ProjectsGridSection({
	projects,
	categories,
	locale = 'en',
	initialCount,
	hrefForSlug,
	ariaLabel,
	className = 'projects-listing-section pb-12 pt-10 md:pb-20 md:pt-14',
}: ProjectsGridSectionProps) {
	const sorted = useMemo(() => projects, [projects]);
	const [visibleCount, setVisibleCount] = useState(() => Math.max(1, initialCount));
	const [isLoadingMore, setIsLoadingMore] = useState(false);
	const loadMoreRef = useRef<HTMLDivElement | null>(null);
	const loadingLockRef = useRef(false);

	const visible = sorted.slice(0, visibleCount);
	const hasMore = visibleCount < sorted.length;
	const contactHref = withLocalePrefix(pagePath(CONTACT_PAGE_SLUG), locale);
	const contactCta = locale === 'pt' ? 'Contato' : 'Contact';
	const loadingLabel = locale === 'pt' ? 'Carregando mais projetos' : 'Loading more projects';

	const loadMore = useCallback(() => {
		if (loadingLockRef.current) return;
		if (visibleCount >= sorted.length) return;

		loadingLockRef.current = true;
		setIsLoadingMore(true);

		// Skeletons primeiro; depois entra o lote (efeito similar ao feed do Instagram).
		window.setTimeout(() => {
			setVisibleCount((count) => Math.min(count + PROJECTS_BATCH_SIZE, sorted.length));
			loadingLockRef.current = false;
			setIsLoadingMore(false);
		}, 420);
	}, [sorted.length, visibleCount]);

	useEffect(() => {
		setVisibleCount(Math.max(1, initialCount));
		loadingLockRef.current = false;
		setIsLoadingMore(false);
	}, [initialCount, sorted.length]);

	useEffect(() => {
		if (!hasMore) return;

		const node = loadMoreRef.current;
		if (!node) return;

		const observer = new IntersectionObserver(
			(entries) => {
				if (entries.some((entry) => entry.isIntersecting)) {
					loadMore();
				}
			},
			{ root: null, rootMargin: '240px 0px', threshold: 0 },
		);

		observer.observe(node);
		return () => observer.disconnect();
	}, [hasMore, loadMore, visibleCount]);

	if (!sorted.length) return null;

	return (
		<section className={className} aria-label={ariaLabel ?? (locale === 'pt' ? 'Projetos' : 'Projects')}>
			<div className="projects-listing-grid">
				{visible.map((project, index) => (
					<ProjectGridCard
						key={project.id}
						project={project}
						categories={categories}
						span={getGridSpan(index)}
						href={hrefForSlug(project.slug)}
						locale={locale}
					/>
				))}

				{isLoadingMore && hasMore
					? Array.from({ length: PROJECTS_BATCH_SIZE }, (_, index) => (
							<div
								key={`skeleton-${visibleCount}-${index}`}
								className="selected-projects-item selected-projects-item--quarter"
								aria-hidden
							>
								<div className="selected-projects-card-image is-loading">
									<span className="selected-projects-card-shimmer" />
								</div>
								<div className="selected-projects-card-title-skeleton mt-4 md:mt-5" />
							</div>
						))
					: null}
			</div>

			{hasMore ? (
				<div ref={loadMoreRef} className="projects-listing-load-more" aria-live="polite">
					{isLoadingMore ? (
						<div className="projects-listing-spinner" role="status">
							<span className="projects-listing-spinner-dot" />
							<span className="screen-reader-text">{loadingLabel}</span>
						</div>
					) : (
						<div className="h-8 w-full" aria-hidden />
					)}
				</div>
			) : null}

			<div className="selected-projects-ctas mt-12 md:mt-16">
				<Link href={contactHref} className="selected-projects-cta font-hk">
					{contactCta}
				</Link>
			</div>
		</section>
	);
}
