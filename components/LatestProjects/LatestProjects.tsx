'use client';

import Image from 'next/image';
import Link from 'next/link';
import { useCallback, useRef } from 'react';

import { sortProjectsByDate } from '../../lib/sortProjects';
import type { Project, Projects } from '../../types';

const MAX_LATEST = 12;

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
	const latest = sortProjectsByDate(projects).slice(0, MAX_LATEST);

	const scrollBy = useCallback((direction: -1 | 1) => {
		const el = scrollRef.current;
		if (!el) return;

		const card = el.querySelector<HTMLElement>('[data-latest-card]');
		const gap = 16;
		const amount = (card?.offsetWidth ?? 300) + gap;

		el.scrollBy({ left: direction * amount, behavior: 'smooth' });
	}, []);

	if (!latest.length) return null;

	const title = locale === 'pt' ? 'Últimos' : 'The Latest';
	const prevLabel = locale === 'pt' ? 'Projetos anteriores' : 'Previous projects';
	const nextLabel = locale === 'pt' ? 'Próximos projetos' : 'Next projects';

	return (
		<section className="latest-projects py-12 md:py-20" aria-label={title}>
			<div className="mb-8 flex items-center justify-between px-7 md:mb-10">
				<h2 className="text-base font-medium text-(--fg) md:text-lg">
					{title} <span aria-hidden>↘</span>
				</h2>

				<div className="flex items-center gap-2">
					<button
						type="button"
						onClick={() => scrollBy(-1)}
						className="latest-projects-nav-btn"
						aria-label={prevLabel}
					>
						<span aria-hidden>←</span>
					</button>
					<button
						type="button"
						onClick={() => scrollBy(1)}
						className="latest-projects-nav-btn"
						aria-label={nextLabel}
					>
						<span aria-hidden>→</span>
					</button>
				</div>
			</div>

			<div ref={scrollRef} className="latest-projects-scroll px-7">
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
	return (
		<Link href={href} data-latest-card className="latest-projects-card group block shrink-0">
			<div className="latest-projects-card-image relative overflow-hidden bg-(--surface)">
				{project.image_full ? (
					<Image
						src={project.image_full}
						alt={project.title ?? project.slug}
						width={640}
						height={480}
						sizes="(max-width: 768px) 72vw, 25vw"
						className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
					/>
				) : null}
			</div>
		</Link>
	);
}
