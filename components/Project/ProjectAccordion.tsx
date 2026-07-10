'use client';

import { useState } from 'react';

import BezierDivider from '../BezierDivider/BezierDivider';
import type { ProjectAccordionSection } from '../../lib/parseProjectContent';

type ProjectAccordionProps = {
	sections: ProjectAccordionSection[];
	defaultOpenIndex?: number;
};

export default function ProjectAccordion({ sections, defaultOpenIndex = 0 }: ProjectAccordionProps) {
	const [openIndex, setOpenIndex] = useState(defaultOpenIndex);

	const visibleSections = sections.filter((section) => section.body.trim());

	if (!visibleSections.length) return null;

	return (
		<div className="project-accordion">
			<BezierDivider />
			{visibleSections.map((section, index) => {
				const isOpen = openIndex === index;

				return (
					<div key={section.title}>
						<button
							type="button"
							className="accordion-trigger flex w-full items-center justify-between gap-4 py-5 text-left"
							onClick={() => setOpenIndex(isOpen ? -1 : index)}
							aria-expanded={isOpen}
						>
							<span className={`accordion-trigger-title font-hk${isOpen ? ' is-open' : ''}`}>
								{section.title}
							</span>
							<span
								className={`accordion-trigger-icon text-lg leading-none${isOpen ? ' is-open text-(--fg)' : ' text-(--muted)'}`}
								aria-hidden
							>
								+
							</span>
						</button>

						<div className={`accordion-panel${isOpen ? ' is-open' : ''}`} aria-hidden={!isOpen}>
							<div className="accordion-panel-inner">
								<div
									className="accordion-panel-content project-accordion-body pb-5 text-sm leading-relaxed text-(--muted) md:text-base"
									dangerouslySetInnerHTML={{ __html: section.body }}
								/>
							</div>
						</div>
						<BezierDivider />
					</div>
				);
			})}
		</div>
	);
}
