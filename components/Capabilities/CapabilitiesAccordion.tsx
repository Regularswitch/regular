'use client';

import Image from 'next/image';
import { useState } from 'react';

import type { CapabilitySection } from '../../lib/capabilitiesDefaults';
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
		<div className="capabilities-accordion divide-y divide-white/10 border-y border-white/10">
			{sections.map((section, index) => {
				const isOpen = openIndex === index;
				const imageSrc = section.image ? (wpMediaUrl(section.image) ?? section.image) : undefined;

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
							<div className="capabilities-accordion-panel pb-8 pt-2">
								<div className="grid items-start gap-8 md:grid-cols-2 md:gap-12">
									{imageSrc ? (
										<div className="capabilities-accordion-image relative aspect-square overflow-hidden rounded-xl bg-(--surface)">
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
						) : null}
					</div>
				);
			})}
		</div>
	);
}
