'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

import NotFoundCenterBlob from './NotFoundCenterBlob';
import { DEFAULT_BLOB_VISUAL } from '../../lib/blobDefaults';
import type { BlobVisual } from '../../types';

type NotFoundPageProps = {
	blob?: BlobVisual;
};

const COPY = {
	en: {
		home: 'Take me home',
		labels: [
			{ text: 'Page not found', className: 'not-found-label not-found-label--tl' },
			{ text: 'Awkward', className: 'not-found-label not-found-label--tr' },
			{ text: 'Error', className: 'not-found-label not-found-label--ml' },
			{ text: 'Oops', className: 'not-found-label not-found-label--mr' },
			{ text: 'Nothing to see here', className: 'not-found-label not-found-label--br' },
			{ text: 'Shit happens', className: 'not-found-label not-found-label--bl' },
		],
	},
	pt: {
		home: 'Voltar ao início',
		labels: [
			{ text: 'Página não encontrada', className: 'not-found-label not-found-label--tl' },
			{ text: 'Estranho', className: 'not-found-label not-found-label--tr' },
			{ text: 'Erro', className: 'not-found-label not-found-label--ml' },
			{ text: 'Ops', className: 'not-found-label not-found-label--mr' },
			{ text: 'Nada pra ver aqui', className: 'not-found-label not-found-label--br' },
			{ text: 'Faz parte', className: 'not-found-label not-found-label--bl' },
		],
	},
} as const;

export default function NotFoundPage({ blob = DEFAULT_BLOB_VISUAL }: NotFoundPageProps) {
	const pathname = usePathname() ?? '';
	const locale = pathname.startsWith('/PT') ? 'pt' : 'en';
	const copy = COPY[locale];
	const homeHref = locale === 'pt' ? '/PT' : '/';

	return (
		<section className="not-found-page" aria-labelledby="not-found-title">
			<div className="not-found-stage">
				{copy.labels.map((label) => (
					<p key={label.text} className={label.className}>
						{label.text}
					</p>
				))}

				<div className="not-found-code" id="not-found-title" aria-label={locale === 'pt' ? 'Erro 404' : 'Error 404'}>
					<span className="not-found-digit" aria-hidden="true">
						4
					</span>

					<div className="not-found-blob-slot">
						<NotFoundCenterBlob blob={blob} />
					</div>

					<span className="not-found-digit" aria-hidden="true">
						4
					</span>
				</div>
			</div>

			<Link href={homeHref} className="selected-projects-cta font-hk not-found-cta">
				{copy.home}
			</Link>
		</section>
	);
}
