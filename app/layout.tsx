import type { ReactNode } from 'react';
import { headers } from 'next/headers';
import { GetBlobVisualApi, GetFooterByLocale, GetHeaderNavApi, GetLegalByLocale, GetSiteUiApi } from '../components/ApiWp';
import CustomCursor from '../components/CustomCursor/CustomCursor';
import FooterComponents from '../components/FooterComponents';
import { FooterSocialProvider } from '../components/Footer/FooterSocialProvider';
import Header from '../components/Header';
import { LegalPoliciesProvider } from '../components/Legal/LegalPoliciesProvider';
import ScrollProgressBar from '../components/ScrollProgressBar';
import JsonLd from '../components/Seo/JsonLd';
import { SiteUiProvider } from '../components/SiteUi/SiteUiProvider';
import { buildNavActiveGradient, resolveBlobVisual } from '../lib/site/blobDefaults';
import { hankenGrotesk } from '../lib/config/fonts';
import { getBaseUrl } from '../lib/config/getBaseUrl';
import { fetchSeoOrgSchema, resolveOrgJsonLd, buildWebSiteJsonLd } from '../lib/seo/schema';
import { buildSiteUiWithHeaderNav } from '../lib/site/resolveSiteUi';
import '../styles/globals.css';

export const metadata = {
	metadataBase: new URL(getBaseUrl()),
	title: {
		default: 'RegularSwitch',
		template: '%s',
	},
	description:
		'Franco-Brazilian design studio based in São Paulo. Brand strategy, visual identity, branding and generative design.',
};

/** Permite dados frescos do WP (footer social, legal) em dev/local. */
export const revalidate = 0;

const THEME_BOOT_SCRIPT = `(function(){try{var m=document.cookie.match(/(?:^|; )theme=([^;]*)/);var t=m?decodeURIComponent(m[1]):'dark';var r=document.documentElement;if(t==='light')r.classList.remove('dark');else r.classList.add('dark');}catch(e){}})();`;

export default async function RootLayout({ children }: { children: ReactNode }) {
	const localeHeader = (await headers()).get('x-rs-locale');
	const locale = localeHeader === 'pt' ? 'pt' : 'en';

	const [footerEn, footerPt, legalEn, legalPt, siteUi, headerNav, blobVisualRaw, seoSchema] =
		await Promise.all([
			GetFooterByLocale('en'),
			GetFooterByLocale('pt'),
			GetLegalByLocale('en'),
			GetLegalByLocale('pt'),
			GetSiteUiApi(),
			GetHeaderNavApi(),
			GetBlobVisualApi(),
			fetchSeoOrgSchema(),
		]);
	const blob = resolveBlobVisual(blobVisualRaw);
	const blobNavGradient = buildNavActiveGradient(blob.palette);
	const orgJsonLd = resolveOrgJsonLd(seoSchema, locale);
	const websiteJsonLd = buildWebSiteJsonLd(locale);

	return (
		<html
			lang={locale === 'pt' ? 'pt-BR' : 'en'}
			className={`${hankenGrotesk.variable} dark`}
			style={{ ['--blob-nav-gradient' as string]: blobNavGradient }}
			suppressHydrationWarning
		>
			<head>
				<meta name="color-scheme" content="dark light" />
				<script
					id="theme-boot"
					dangerouslySetInnerHTML={{ __html: THEME_BOOT_SCRIPT }}
				/>
				<JsonLd id="org-jsonld" data={orgJsonLd} />
				<JsonLd id="website-jsonld" data={websiteJsonLd} />
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
