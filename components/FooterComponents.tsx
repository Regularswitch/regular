'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useCallback, useEffect, useMemo, useState } from 'react';

import type { FooterContent, FooterLink } from '../types';
import FontVariante from './FontVariante';
import { DEFAULT_FOOTER_EN, DEFAULT_FOOTER_PT } from './Footer/footerDefaults';
import LegalPolicyModal from './Legal/LegalPolicyModal';
import { getCookie } from './Translate';
import { getContactMailto, getNewsletterHref } from '../lib/site/siteLinks';

type FooterLocale = 'en' | 'pt';
type LegalModalKind = 'privacy' | 'cookies' | null;

type FooterComponentsProps = {
	footerEn: FooterContent | null;
	footerPt: FooterContent | null;
};

function localeFromPathname(pathname: string): FooterLocale {
	return pathname.startsWith('/PT') ? 'pt' : 'en';
}

function resolveLocale(pathname: string): FooterLocale {
	if (pathname.startsWith('/PT')) return 'pt';
	return getCookie('language') === 'PT' ? 'pt' : 'en';
}

function withPrefix(href: string, locale: FooterLocale) {
	if (href.startsWith('http') || href.startsWith('mailto:')) return href;
	const prefix = locale === 'pt' ? '/PT' : '';
	return `${prefix}${href}`.replace(/^\/\//, '/') || href;
}

function isExternal(href: string) {
	return href.startsWith('http') && !href.includes('regularswitch');
}

/** Prefer env placeholders for Contato / Newsletter até o cliente confirmar destinos. */
function resolveFooterLinks(links: FooterLink[]): FooterLink[] {
	return links.map((item) => {
		const key = item.title.replace(/<[^>]+>/g, '').trim().toLowerCase();
		if (key === 'contact' || key === 'contato') {
			return { ...item, href: getContactMailto() };
		}
		if (key === 'newsletter') {
			return { ...item, href: getNewsletterHref() };
		}
		return item;
	});
}

export default function FooterComponents({ footerEn, footerPt }: FooterComponentsProps) {
	const pathname = usePathname() ?? '';
	const [locale, setLocale] = useState<FooterLocale>(() => localeFromPathname(pathname));
	const [modal, setModal] = useState<LegalModalKind>(null);

	useEffect(() => {
		setLocale(resolveLocale(pathname));
	}, [pathname]);

	const fallback = locale === 'pt' ? DEFAULT_FOOTER_PT : DEFAULT_FOOTER_EN;
	const fromWp = locale === 'pt' ? footerPt : footerEn;
	const { brandMark, links: rawLinks, legal } = fromWp ?? fallback;
	const links = resolveFooterLinks(rawLinks);

	const privacyBody = (legal.privacyBody?.trim() || fallback.legal.privacyBody || '').trim();
	const cookiesBody = (legal.cookiesBody?.trim() || fallback.legal.cookiesBody || '').trim();

	const closeModal = useCallback(() => setModal(null), []);
	const closeLabel = locale === 'pt' ? 'Fechar' : 'Close';

	const modalTitle = useMemo(() => {
		if (modal === 'privacy') return legal.privacy;
		if (modal === 'cookies') return legal.cookies;
		return '';
	}, [modal, legal.privacy, legal.cookies]);

	const modalBody = useMemo(() => {
		if (modal === 'privacy') return privacyBody;
		if (modal === 'cookies') return cookiesBody;
		return '';
	}, [modal, privacyBody, cookiesBody]);

	return (
		<footer className="site-footer mt-10 border-t border-white/10 pt-12 md:mt-14 md:pt-16">
			<div className="grid gap-10 px-7 md:w-1/2 md:grid-cols-3 md:gap-8">
				{links.map((item: FooterLink) => (
					<Link
						key={`${item.title}-${item.href}`}
						href={withPrefix(item.href, locale)}
						className="group block max-w-xs"
						{...(item.external || isExternal(item.href)
							? { target: '_blank', rel: 'noopener noreferrer' }
							: {})}
					>
						<p
							className="font-hk text-base font-bold text-(--fg) md:text-lg"
							dangerouslySetInnerHTML={{ __html: item.title }}
						/>
						<p
							className="mt-1 text-sm text-(--muted) transition-opacity group-hover:opacity-80"
							dangerouslySetInnerHTML={{ __html: item.subtitle }}
						/>
					</Link>
				))}
			</div>

			<div className="site-footer-brand mt-14 w-full overflow-hidden px-7 md:mt-20">
				<FontVariante text={brandMark} align="justify" splitOnMobile />
			</div>

			<nav
				className="mt-8 flex flex-wrap items-center gap-x-2 gap-y-1 px-7 pb-10 text-sm text-(--muted) md:mt-10"
				aria-label="Legal"
			>
				<span className="inline-flex items-center gap-2">
					<span>{legal.brand}</span>
				</span>
				<span className="inline-flex items-center gap-2">
					<span aria-hidden>/</span>
					<button
						type="button"
						className="transition-opacity hover:opacity-80 hover:text-(--fg)"
						onClick={() => setModal('privacy')}
					>
						{legal.privacy}
					</button>
				</span>
				<span className="inline-flex items-center gap-2">
					<span aria-hidden>/</span>
					<button
						type="button"
						className="transition-opacity hover:opacity-80 hover:text-(--fg)"
						onClick={() => setModal('cookies')}
					>
						{legal.cookies}
					</button>
				</span>
			</nav>

			<LegalPolicyModal
				open={modal !== null && modalBody !== ''}
				title={modalTitle}
				bodyHtml={modalBody}
				onClose={closeModal}
				closeLabel={closeLabel}
			/>
		</footer>
	);
}
