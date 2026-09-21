'use client';

import { useState } from 'react';

import type { CapabilitiesFaqItem } from '../../types';
import BezierDivider from '../BezierDivider/BezierDivider';
import { AccordionPlusIcon } from '../SiteIcons';

type CapabilitiesFaqProps = {
	title?: string;
	items?: CapabilitiesFaqItem[];
};

export default function CapabilitiesFaq({ title, items }: CapabilitiesFaqProps) {
	const list = (items ?? []).filter((item) => item.question?.trim() && item.answer?.trim());
	const [openIndex, setOpenIndex] = useState(0);

	if (!list.length) return null;

	const heading = title?.trim() || 'FAQ';

	return (
		<section className="capabilities-faq py-10 md:py-14" aria-label={heading}>
			<h2 className="capabilities-faq-heading font-hk text-(--fg)">{heading}</h2>

			<div className="capabilities-faq-list mt-8 md:mt-10">
				<BezierDivider />
				{list.map((item, index) => {
					const isOpen = openIndex === index;
					const panelId = `capabilities-faq-panel-${index}`;

					return (
						<div key={`${index}-${item.question}`}>
							<button
								type="button"
								className="accordion-trigger flex w-full items-center justify-between gap-4 py-5 text-left"
								onClick={() => setOpenIndex(isOpen ? -1 : index)}
								aria-expanded={isOpen}
								aria-controls={panelId}
							>
								<span
									className={`accordion-trigger-title capabilities-faq-question font-hk normal-case${isOpen ? ' is-open' : ''}`}
								>
									{item.question}
								</span>
								<span
									className={`accordion-trigger-icon text-lg leading-none${isOpen ? ' is-open text-(--fg)' : ' text-(--muted)'}`}
									aria-hidden
								>
									<AccordionPlusIcon />
								</span>
							</button>

							<div
								id={panelId}
								className={`accordion-panel${isOpen ? ' is-open' : ''}`}
								aria-hidden={!isOpen}
							>
								<div className="accordion-panel-inner">
									<div
										className="accordion-panel-content capabilities-faq-answer pb-6 text-(--muted)"
										dangerouslySetInnerHTML={{ __html: item.answer }}
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
