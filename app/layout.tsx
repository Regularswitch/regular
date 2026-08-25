import type { ReactNode } from 'react';
import Script from 'next/script';
import { GetBlobVisualApi, GetFooterByLocale, GetHeaderNavApi, GetLegalByLocale, GetSiteUiApi } from '../components/ApiWp';
import CustomCursor from '../components/CustomCursor/CustomCursor';
import FooterComponents from '../components/FooterComponents';
import { FooterSocialProvider } from '../components/Footer/FooterSocialProvider';
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

/** Permite dados frescos do WP (footer social, legal) em dev/local. */
export const revalidate = 0;

const THEME_BOOT_SCRIPT = `(function(){try{var m=document.cookie.match(/(?:^|; )theme=([^;]*)/);var t=m?decodeURIComponent(m[1]):'dark';var r=document.documentElement;if(t==='light')r.classList.remove('dark');else r.classList.add('dark');}catch(e){}})();`;

const ORG_JSON_LD = {
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
};

export default async function RootLayout({ children }: { children: ReactNode }) {
	const [footerEn, footerPt, legalEn, legalPt, siteUi, headerNav, blobVisualRaw] = await Promise.all([
		GetFooterByLocale('en'),
		GetFooterByLocale('pt'),
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
			</head>
			<body>
				<Script id="theme-boot" strategy="beforeInteractive">
					{THEME_BOOT_SCRIPT}
				</Script>
				<Script
					id="org-jsonld"
					type="application/ld+json"
					strategy="beforeInteractive"
					dangerouslySetInnerHTML={{ __html: JSON.stringify(ORG_JSON_LD) }}
				/>
				<CustomCursor palette={blob.palette} />
				<ScrollProgressBar />
				<SiteUiProvider siteUi={buildSiteUiWithHeaderNav(siteUi, headerNav)}>
					<LegalPoliciesProvider
						footerEn={footerEn}
						footerPt={footerPt}
						legalEn={legalEn}
						legalPt={legalPt}
					>
						<FooterSocialProvider footerEn={footerEn} footerPt={footerPt}>
							<Header />
							<main className="pt-16 px-7 sm:pt-14 lg:pt-18">{children}</main>
							<FooterComponents footerEn={footerEn} footerPt={footerPt} />
						</FooterSocialProvider>
					</LegalPoliciesProvider>
				</SiteUiProvider>
			</body>
		</html>
	);
}
