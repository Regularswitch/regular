'use client';

import Link from 'next/link';
import { useState } from 'react';

import type { CapabilitiesFaqItem } from '../../types';
import { CONTACT_PAGE_SLUG, pagePath } from '../../lib/site/pageSlugs';
import { withLocalePrefix } from '../../lib/site/resolveSiteUi';
import BezierDivider from '../BezierDivider/BezierDivider';
import { AccordionPlusIcon } from '../SiteIcons';

type CapabilitiesFaqProps = {
	title?: string;
	items?: CapabilitiesFaqItem[];
	locale?: 'en' | 'pt';
};

export default function CapabilitiesFaq({ title, items, locale = 'en' }: CapabilitiesFaqProps) {
	const list = (items ?? []).filter((item) => item.question?.trim() && item.answer?.trim());
	const [openIndex, setOpenIndex] = useState(-1);

	if (!list.length) return null;

	const heading = title?.trim() || (locale === 'pt' ? 'Perguntas frequentes' : 'FAQ');
	const contactLabel = locale === 'pt' ? 'Contato' : 'Contact';
	const contactHref = withLocalePrefix(pagePath(CONTACT_PAGE_SLUG), locale);

	return (
		<section
			className="capabilities-faq flex flex-col py-4 md:grid md:grid-cols-2 md:items-start md:gap-12 md:py-6 lg:gap-16"
			aria-label={heading}
		>
			<div className="capabilities-faq-intro min-w-0">
				<h2 className="capabilities-faq-heading font-hk text-(--fg)">{heading}</h2>
				<Link href={contactHref} className="selected-projects-cta font-hk mt-8 inline-flex md:mt-10">
					{contactLabel}
				</Link>
			</div>

			<div className="capabilities-faq-list mt-10 min-w-0 md:mt-0">
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
