'use client';

import { useState } from 'react';

import type { ProjectAccordionSection } from '../../lib/parseProjectContent';

type ProjectAccordionProps = {
	sections: ProjectAccordionSection[];
};

export default function ProjectAccordion({ sections }: ProjectAccordionProps) {
	const [openIndex, setOpenIndex] = useState(0);

	const visibleSections = sections.filter((section) => section.body.trim());

	if (!visibleSections.length) return null;

	return (
		<div className="project-accordion divide-y divide-white/10 border-y border-white/10">
			{visibleSections.map((section, index) => {
				const isOpen = openIndex === index;

				return (
					<div key={section.title}>
						<button
							type="button"
							className="flex w-full items-center justify-between gap-4 py-5 text-left"
							onClick={() => setOpenIndex(isOpen ? -1 : index)}
							aria-expanded={isOpen}
						>
							<span className="font-hk text-xs font-semibold tracking-[0.18em] text-(--fg) md:text-sm">
								{section.title}
							</span>
							<span className="text-lg leading-none text-(--muted)" aria-hidden>
								{isOpen ? '−' : '+'}
							</span>
						</button>

						{isOpen ? (
							<div
								className="project-accordion-body pb-5 text-sm leading-relaxed text-(--muted) md:text-base"
								dangerouslySetInnerHTML={{ __html: section.body }}
							/>
						) : null}
					</div>
				);
			})}
		</div>
	);
}
