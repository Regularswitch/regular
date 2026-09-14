'use client';

import type { CapabilitiesFaqItem } from '../../types';

type CapabilitiesFaqProps = {
	title?: string;
	items?: CapabilitiesFaqItem[];
};

export default function CapabilitiesFaq({ title, items }: CapabilitiesFaqProps) {
	const list = (items ?? []).filter((item) => item.question?.trim() && item.answer?.trim());
	if (!list.length) return null;

	const heading = title?.trim() || 'FAQ';

	return (
		<section className="capabilities-faq py-10 md:py-14" aria-label={heading}>
			<h2 className="font-hk text-xl font-medium tracking-tight text-(--fg) md:text-2xl">{heading}</h2>
			<dl className="mt-8 space-y-8 md:mt-10">
				{list.map((item) => (
					<div key={item.question} className="max-w-3xl">
						<dt className="font-hk text-base font-medium text-(--fg) md:text-lg">{item.question}</dt>
						<dd
							className="capabilities-faq-answer mt-3 font-hk text-(--muted)"
							dangerouslySetInnerHTML={{ __html: item.answer }}
						/>
					</div>
				))}
			</dl>
		</section>
	);
}
