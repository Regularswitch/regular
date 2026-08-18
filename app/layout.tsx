import type { ReactNode } from 'react';
import { GetBlobVisualApi, GetFooterApi, GetHeaderNavApi, GetLegalByLocale, GetSiteUiApi } from '../components/ApiWp';
import CustomCursor from '../components/CustomCursor/CustomCursor';
import FooterComponents from '../components/FooterComponents';
import Header from '../components/Header';
import { LegalPoliciesProvider } from '../components/Legal/LegalPoliciesProvider';
import ScrollProgressBar from '../components/ScrollProgressBar';
import { SiteUiProvider } from '../components/SiteUi/SiteUiProvider';
import { buildNavActiveGradient, resolveBlobVisual } from '../lib/site/blobDefaults';
import { hankenGrotesk } from '../lib/config/fonts';
import { buildSiteUiWithHeaderNav } from '../lib/site/resolveSiteUi';
import '../styles/globals.css';

export const metadata = {
	title: 'Regular Switch',
};

/** Permite cache ISR da árvore — tema aplica no client (script + ThemeToggle). */
export const revalidate = 60;

const THEME_BOOT_SCRIPT = `(function(){try{var m=document.cookie.match(/(?:^|; )theme=([^;]*)/);var t=m?decodeURIComponent(m[1]):'dark';var r=document.documentElement;if(t==='light')r.classList.remove('dark');else r.classList.add('dark');}catch(e){}})();`;

export default async function RootLayout({ children }: { children: ReactNode }) {
	const [footerEn, footerPt, legalEn, legalPt, siteUi, headerNav, blobVisualRaw] = await Promise.all([
		GetFooterApi({ slug: 'en' }),
		GetFooterApi({ slug: 'pt' }),
		GetLegalByLocale('en'),
		GetLegalByLocale('pt'),
		GetSiteUiApi(),
		GetHeaderNavApi(),
		GetBlobVisualApi(),
	]);
	const blob = resolveBlobVisual(blobVisualRaw);
	const blobNavGradient = buildNavActiveGradient(blob.palette);

	return (
		<html
			lang="en"
			className={`${hankenGrotesk.variable} dark`}
			style={{ ['--blob-nav-gradient' as string]: blobNavGradient }}
			suppressHydrationWarning
		>
			<head>
				<meta name="color-scheme" content="dark light" />
				<script dangerouslySetInnerHTML={{ __html: THEME_BOOT_SCRIPT }} />
				<script
					type="application/ld+json"
					dangerouslySetInnerHTML={{
						__html: JSON.stringify({
							'@context': 'http://schema.org',
							'@type': 'Organization',
							name: 'RSW Regular Switch',
							url: 'https://regularswitch.com/',
							contactPoint: [
								{
									'@type': 'ContactPoint',
									telephone: '+55 11 94540-8448',
									contactType: 'customer service',
								},
							],
						}),
					}}
				/>
			</head>
			<body>
				<CustomCursor palette={blob.palette} />
				<ScrollProgressBar />
				<SiteUiProvider siteUi={buildSiteUiWithHeaderNav(siteUi, headerNav)}>
					<LegalPoliciesProvider
						footerEn={footerEn}
						footerPt={footerPt}
						legalEn={legalEn}
						legalPt={legalPt}
					>
						<Header />
						<main className="pt-16 px-7 sm:pt-14 lg:pt-18">{children}</main>
						<FooterComponents footerEn={footerEn} footerPt={footerPt} />
					</LegalPoliciesProvider>
				</SiteUiProvider>
			</body>
		</html>
	);
}
