'use client';

import Image from 'next/image';
import { useMemo, useState } from 'react';

import BezierDivider from '../BezierDivider/BezierDivider';
import { AccordionPlusIcon } from '../SiteIcons';
import type { AboutAccordionSection } from '../../lib/content/about/defaults';
import { wpMediaUrl } from '../../lib/wp/mediaUrl';

type AboutAccordionPanelProps = {
	sections: AboutAccordionSection[];
};

function sectionImageUrl(section?: AboutAccordionSection): string | undefined {
	if (!section?.image) return undefined;
	return wpMediaUrl(section.image) ?? section.image;
}

export default function AboutAccordionPanel({ sections }: AboutAccordionPanelProps) {
	const visibleSections = useMemo(
		() => sections.filter((section) => section.body.trim()),
		[sections],
	);

	const [openIndex, setOpenIndex] = useState(() => (visibleSections.length > 0 ? 0 : -1));
	const [pinnedImage, setPinnedImage] = useState<string | undefined>(() =>
		sectionImageUrl(visibleSections[0]),
	);

	const activeSection = openIndex >= 0 ? visibleSections[openIndex] : undefined;
	const activeImage = sectionImageUrl(activeSection) ?? pinnedImage;

	if (!visibleSections.length) return null;

	return (
		<section className="about-accordion-section md:grid md:grid-cols-2 md:items-start md:gap-12 lg:gap-16">
			{/* Coluna esquerda sempre reservada no desktop — acordeão permanece à direita ao fechar. */}
			<div className="about-side-image relative mb-10 aspect-square min-w-0 overflow-hidden rounded-[5px] bg-(--surface) md:sticky md:top-28 md:mb-0">
				{activeImage ? (
					<Image
						key={activeImage}
						src={activeImage}
						alt=""
						fill
						sizes="(max-width: 768px) 100vw, 45vw"
						className="object-cover object-center transition-opacity duration-300"
					/>
				) : null}
			</div>

			<div className="project-accordion min-w-0 md:col-start-2">
				<BezierDivider />
				{visibleSections.map((section, index) => {
					const isOpen = openIndex === index;

					return (
						<div key={section.title}>
							<button
								type="button"
								className="accordion-trigger flex w-full items-center justify-between gap-4 py-5 text-left"
								onClick={() => {
									if (isOpen) {
										setOpenIndex(-1);
										return;
									}
									const nextImage = sectionImageUrl(section);
									if (nextImage) setPinnedImage(nextImage);
									setOpenIndex(index);
								}}
								aria-expanded={isOpen}
							>
								<span className={`accordion-trigger-title font-hk normal-case${isOpen ? ' is-open' : ''}`}>
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
