'use client';

import Image from 'next/image';
import { useMemo, useState } from 'react';

import BezierDivider from '../BezierDivider/BezierDivider';
import { AccordionPlusIcon } from '../SiteIcons';
import type { AboutAccordionSection } from '../../lib/aboutDefaults';
import { wpMediaUrl } from '../../lib/wpMediaUrl';

type AboutAccordionPanelProps = {
	sections: AboutAccordionSection[];
};

export default function AboutAccordionPanel({ sections }: AboutAccordionPanelProps) {
	const [openIndex, setOpenIndex] = useState(0);

	const visibleSections = useMemo(
		() => sections.filter((section) => section.body.trim()),
		[sections],
	);

	const activeSection = openIndex >= 0 ? visibleSections[openIndex] : undefined;
	const activeImage = activeSection?.image
		? (wpMediaUrl(activeSection.image) ?? activeSection.image)
		: undefined;

	if (!visibleSections.length) return null;

	return (
		<section className="about-accordion-section md:grid md:grid-cols-2 md:items-start md:gap-12 lg:gap-16">
			{activeImage ? (
				<div className="about-side-image relative mb-10 aspect-square min-w-0 overflow-hidden rounded-xl bg-(--surface) md:sticky md:top-28 md:mb-0">
					<Image
						key={activeImage}
						src={activeImage}
						alt=""
						fill
						sizes="(max-width: 768px) 100vw, 45vw"
						className="object-cover object-center transition-opacity duration-300"
					/>
				</div>
			) : null}

			<div className="project-accordion min-w-0">
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
									<AccordionPlusIcon />
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
		</section>
	);
}
