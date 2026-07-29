'use client';

import Image from 'next/image';
import { useState } from 'react';

import BezierDivider from '../BezierDivider/BezierDivider';
import { AccordionPlusIcon } from '../SiteIcons';
import type { CapabilitySection } from '../../lib/capabilitiesDefaults';
import { toSentenceCaseHtml } from '../../lib/sentenceCase';
import { wpMediaUrl } from '../../lib/wpMediaUrl';

type CapabilitiesAccordionProps = {
	sections: CapabilitySection[];
	defaultOpenIndex?: number;
};

export default function CapabilitiesAccordion({
	sections,
	defaultOpenIndex = -1,
}: CapabilitiesAccordionProps) {
	const [openIndex, setOpenIndex] = useState(defaultOpenIndex);

	if (!sections.length) return null;

	return (
		<div className="capabilities-accordion">
			<BezierDivider />
			{sections.map((section, index) => {
				const isOpen = openIndex === index;
				const imageSrc = section.image ? (wpMediaUrl(section.image) ?? section.image) : undefined;

				return (
					<div key={section.title}>
						<button
							type="button"
							className="accordion-trigger flex w-full items-center justify-between gap-4 py-5 text-left"
							onClick={() => setOpenIndex(isOpen ? -1 : index)}
							aria-expanded={isOpen}
						>
							<span
								className={`accordion-trigger-title accordion-trigger-title--sentence font-hk${isOpen ? ' is-open' : ''}`}
								dangerouslySetInnerHTML={{ __html: toSentenceCaseHtml(section.title) }}
							/>
							<span
								className={`accordion-trigger-icon text-lg leading-none${isOpen ? ' is-open text-(--fg)' : ' text-(--muted)'}`}
								aria-hidden
							>
								<AccordionPlusIcon />
							</span>
						</button>

						<div className={`accordion-panel${isOpen ? ' is-open' : ''}`} aria-hidden={!isOpen}>
							<div className="accordion-panel-inner">
								<div className="accordion-panel-content capabilities-accordion-panel pb-8 pt-2">
									<div className="grid items-start gap-8 md:grid-cols-2 md:gap-12">
										{imageSrc ? (
											<div className="capabilities-accordion-image relative aspect-square overflow-hidden rounded-[5px] bg-(--surface)">
												<Image
													src={imageSrc}
													alt=""
													fill
													sizes="(max-width: 768px) 100vw, 45vw"
													className="object-cover object-center"
												/>
											</div>
										) : null}

										<div
											className={`capabilities-accordion-content font-hk${imageSrc ? '' : ' md:col-span-2'}`}
										>
											{section.lead ? (
												<p className="text-lg leading-snug text-(--fg) md:text-xl md:leading-tight">
													{section.lead}
												</p>
											) : null}

											{section.body ? (
												<div
													className="capabilities-accordion-body mt-5 text-sm leading-relaxed text-(--muted) md:mt-6 md:text-base"
													dangerouslySetInnerHTML={{ __html: section.body }}
												/>
											) : null}

											{section.services && section.services.length > 0 ? (
												<div className="mt-6 md:mt-8">
													{section.servicesTitle ? (
														<p className="text-sm font-semibold text-(--fg) md:text-base">
															{section.servicesTitle}
														</p>
													) : null}
													<ul className="capabilities-accordion-list mt-3 space-y-1.5 text-sm text-(--muted) md:text-base">
														{section.services.map((service) => (
															<li key={service}>{service}</li>
														))}
													</ul>
												</div>
											) : null}
										</div>
									</div>
								</div>
							</div>
						</div>
						<BezierDivider />
					</div>
				);
			})}
		</div>
	);
}
