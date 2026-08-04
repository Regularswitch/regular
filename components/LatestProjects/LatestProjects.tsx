'use client';

import Image from 'next/image';
import Link from 'next/link';
import { useCallback, useRef } from 'react';

import { sortProjectsByDate } from '../../lib/projects/sort';
import { getProjectHeroImage } from '../../lib/projects/images';
import type { Project, Projects } from '../../types';
import { NavChevronLeft, NavChevronRight, SectionHeadingArrow } from '../SiteIcons';
import { useSiteUiLayout, useSiteUiLocale } from '../SiteUi/SiteUiProvider';

type LatestProjectsProps = {
	projects: Projects;
	locale?: 'en' | 'pt';
};

function projectHref(slug: string, locale: 'en' | 'pt') {
	const prefix = locale === 'pt' ? '/PT' : '';
	return `${prefix}/project/${slug}`.replace(/^\/\//, '/') || `/project/${slug}`;
}

export default function LatestProjects({ projects, locale = 'en' }: LatestProjectsProps) {
	const scrollRef = useRef<HTMLDivElement>(null);
	const siteUi = useSiteUiLocale(locale);
	const layout = useSiteUiLayout();
	const count = Math.max(3, Math.min(12, layout.latestCount || 6));
	const latest = sortProjectsByDate(projects).slice(0, count);

	const scrollBy = useCallback((direction: -1 | 1) => {
		const el = scrollRef.current;
		if (!el) return;

		const card = el.querySelector<HTMLElement>('[data-latest-card]');
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

	if (!latest.length) return null;

	const title = siteUi.labels.latestProjects;
	const prevLabel = locale === 'pt' ? 'Projetos anteriores' : 'Previous projects';
	const nextLabel = locale === 'pt' ? 'Próximos projetos' : 'Next projects';
	const showNav = latest.length > 1;

	return (
		<section className="latest-projects py-6 md:py-10" aria-label={title}>
			<div className="mb-8 flex items-center justify-between md:mb-10">
				<h2 className="inline-flex items-center gap-1.5 text-base font-medium text-(--fg) md:text-lg">
					{title}
					<SectionHeadingArrow />
				</h2>

				{showNav ? (
					<div className="flex items-center gap-2">
						<button
							type="button"
							onClick={() => scrollBy(-1)}
							className="latest-projects-nav-btn"
							aria-label={prevLabel}
						>
							<NavChevronLeft />
						</button>
						<button
							type="button"
							onClick={() => scrollBy(1)}
							className="latest-projects-nav-btn"
							aria-label={nextLabel}
						>
							<NavChevronRight />
						</button>
					</div>
				) : null}
			</div>

			<div ref={scrollRef} className="latest-projects-scroll">
				{latest.map((project) => (
					<LatestProjectCard
						key={project.id}
						project={project}
						href={projectHref(project.slug, locale)}
					/>
				))}
			</div>
		</section>
	);
}

function LatestProjectCard({ project, href }: { project: Project; href: string }) {
	const cardImage = getProjectHeroImage(project);

	return (
		<Link href={href} data-latest-card className="latest-projects-card group block shrink-0">
			<div className="latest-projects-card-image relative overflow-hidden bg-(--surface)">
				{cardImage ? (
					<Image
						src={cardImage}
						alt={project.title ?? project.slug}
						width={640}
						height={640}
						sizes="(max-width: 768px) 70vw, 25vw"
						className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
					/>
				) : null}
			</div>
		</Link>
	);
}
