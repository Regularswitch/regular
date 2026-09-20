'use client';

import Link from 'next/link';
import { useCallback, useRef } from 'react';

import { pagePath, PROJECTS_PAGE_SLUG } from '../../lib/site/pageSlugs';
import { isHomeProject } from '../../lib/projects/categories';
import { isFeaturedOnHome } from '../../lib/projects/featured';
import { withLocalePrefix } from '../../lib/site/resolveSiteUi';
import { sortProjectsByDate } from '../../lib/projects/sort';
import type { Category, Projects, SiteUiLabels } from '../../types';
import ProjectGridCard from '../ProjectsListing/ProjectGridCard';
import { NavChevronLeft, NavChevronRight, SectionHeadingArrow } from '../SiteIcons';

/** Quantos cards no carrossel abaixo do destaque. */
const HOME_CAROUSEL_COUNT = 4;

type SelectedProjectsProps = {
	projects: Projects;
	categories: Category[];
	locale?: 'en' | 'pt';
	labels?: Pick<SiteUiLabels, 'selectedProjects' | 'seeMoreProjects'>;
};

export default function SelectedProjects({ projects, categories, locale = 'en', labels }: SelectedProjectsProps) {
	const scrollRef = useRef<HTMLDivElement>(null);

	const sorted = sortProjectsByDate(projects);
	const filtered = sorted.filter((p) => isHomeProject(p, categories));
	/** Já vem filtrado por categoria home na page; não esvaziar se o ID não bater. */
	const homePool = filtered.length > 0 ? filtered : sorted;

	const featured = homePool.find(isFeaturedOnHome) ?? null;
	const carousel = homePool
		.filter((p) => !featured || p.id !== featured.id)
		.slice(0, HOME_CAROUSEL_COUNT);

	const scrollBy = useCallback((direction: -1 | 1) => {
		const el = scrollRef.current;
		if (!el) return;

		const card = el.querySelector<HTMLElement>('[data-selected-card]');
		const styles = getComputedStyle(el);
		const gap = Number.parseFloat(styles.columnGap || styles.gap || '20') || 20;
		const amount = (card?.offsetWidth ?? 300) + gap;
		const maxScroll = Math.max(0, el.scrollWidth - el.clientWidth);
		const edge = 4;

		if (direction > 0 && el.scrollLeft >= maxScroll - edge) {
			el.scrollTo({ left: 0, behavior: 'smooth' });
			return;
		}

		if (direction < 0 && el.scrollLeft <= edge) {
			el.scrollTo({ left: maxScroll, behavior: 'smooth' });
			return;
		}

		el.scrollBy({ left: direction * amount, behavior: 'smooth' });
	}, []);

	if (!featured && !carousel.length) return null;

	const projectsHref = withLocalePrefix(pagePath(PROJECTS_PAGE_SLUG), locale);
	const title = labels?.selectedProjects ?? (locale === 'pt' ? 'Projetos Selecionados' : 'Selected Projects');
	const cta = labels?.seeMoreProjects ?? (locale === 'pt' ? 'Veja mais projetos' : 'See more projects');
	const prevLabel = locale === 'pt' ? 'Projetos anteriores' : 'Previous projects';
	const nextLabel = locale === 'pt' ? 'Próximos projetos' : 'Next projects';
	const showNav = carousel.length > 0;

	return (
		<section className="selected-projects py-6 md:py-10" aria-label={title}>
			<div className="mb-8 md:mb-12">
				<h2 className="inline-flex items-center gap-1.5 text-xl font-medium text-(--fg)">
					{title}
					<SectionHeadingArrow />
				</h2>
			</div>

			{featured ? (
				<div className="selected-projects-featured mb-8 md:mb-12">
					<ProjectGridCard
						project={featured}
						categories={categories}
						span="featured"
						href={withLocalePrefix(`/project/${featured.slug}`, locale)}
						locale={locale}
					/>
				</div>
			) : null}

			{carousel.length > 0 ? (
				<>
					{showNav ? (
						<div className="selected-projects-nav mb-4 flex items-center justify-end gap-2 md:mb-5">
							<button
								type="button"
								onClick={() => scrollBy(-1)}
								className="selected-projects-nav-btn"
								aria-label={prevLabel}
							>
								<NavChevronLeft />
							</button>
							<button
								type="button"
								onClick={() => scrollBy(1)}
								className="selected-projects-nav-btn"
								aria-label={nextLabel}
							>
								<NavChevronRight />
							</button>
						</div>
					) : null}

					<div ref={scrollRef} className="selected-projects-carousel" data-selected-carousel>
						{carousel.map((project) => (
							<div key={project.id} className="selected-projects-carousel-slide" data-selected-card>
								<ProjectGridCard
									project={project}
									categories={categories}
									span="half"
									href={withLocalePrefix(`/project/${project.slug}`, locale)}
									locale={locale}
								/>
							</div>
						))}

						<div className="selected-projects-carousel-cta">
							<Link href={projectsHref} className="selected-projects-cta font-hk">
								{cta}
							</Link>
						</div>
					</div>
				</>
			) : (
				<div className="selected-projects-ctas mt-4">
					<Link href={projectsHref} className="selected-projects-cta font-hk">
						{cta}
					</Link>
				</div>
			)}
		</section>
	);
}
