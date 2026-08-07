import { tipoLinguagens } from "./Language"
import { type Brand, type BlobVisual, type CapabilitiesContent, type Category, type FooterContent, type Intro, type ProjectMeta, type ProjectStructuredData, type Projects, type SiteUiContent } from '../types';
import type { AboutContent } from '../lib/content/about/defaults';
import type { ContactContent } from '../lib/content/contact/defaults';
import type { EducationContent } from '../lib/content/education/defaults';
import type { LegalContent } from '../lib/content/legal/defaults';
import { buildLegalContent } from '../lib/content/legal/build';
import type { ProjectsPageContent } from '../lib/content/projects-page/defaults';
import { wpLangSlug, type WpLocale } from '../lib/wp/localeSlug';
import { normalizeGalleryItems } from '../lib/projects/gallery';
import { getProjectHeroImage, normalizeProjectData } from '../lib/projects/images';
import { excludeProjectTranslationTwins } from '../lib/projects/sort';
import { wpMediaUrl } from '../lib/wp/mediaUrl';
import type { HeaderNavContent } from '../lib/site/resolveSiteUi';

export type data = {
    translate?: tipoLinguagens | string
    _links?: string
    _embed?: string
    slug?: string
    parent?: number
    per_page?: number
    more?: string
    categories?: string
    meta?: string
    links?: []

}

export type responseWpMedia = {
    alt_text?: string
    title?: { rendered: string }
    source_url?: string
    media_details: {
        sizes: {
            medium: {
                source_url: string
            }
            full: {
                source_url: string
            }
        }
    }
}

export type attachment = {
    href?: string
}

export type responseWp = {
    id: number
    slug: string
    link: string
    name?: string
    description?: string
    date?: Date
    title: {
        rendered: string
    }
    excerpt: {
        rendered: string
    }
    content: {
        rendered: string
    }
    featured_media?: string
    _embedded: {
        'wp:featuredmedia': responseWpMedia[]
    }
    "project-category"?: []
    _links?: {
        "wp:attachment"?: attachment[]
    }
    footer_data?: FooterContent
    intro_data?: Intro
    capabilities_data?: CapabilitiesContent
    about_data?: AboutContent
    education_data?: EducationContent
    contact_data?: ContactContent
    legal_data?: LegalContent
    projects_page_data?: ProjectsPageContent
    site_ui_data?: SiteUiContent
    project_data?: ProjectStructuredData
    meta?: Record<string, string>
}

export type listResponseWp = Array<responseWp>

export function porter(payloadWp: listResponseWp): Projects {
    return payloadWp.map((p) => {
        const featuredFull = wpMediaUrl(
            p._embedded?.['wp:featuredmedia']?.[0]?.media_details?.sizes.full?.source_url,
        );
        const featuredMedium = wpMediaUrl(
            p._embedded?.['wp:featuredmedia']?.[0]?.media_details?.sizes?.medium?.source_url,
        );
        const project_data = normalizeProjectData(p.project_data ?? null);
        const cardImage = getProjectHeroImage({ project_data, image_full: featuredFull });

        return {
            id: p.id,
            title: p?.title?.rendered || p.name,
            slug: p.slug,
            link: p.link,
            image_medium: cardImage ?? featuredMedium,
            image_full: cardImage ?? featuredFull,
            content: p?.content?.rendered,
            more: p?.excerpt?.rendered,
            category: p['project-category'],
            description: p.description,
            created_at: p.date,
            image: wpMediaUrl(p?._links?.['wp:attachment']?.[0]?.href),
            project_data,
        };
    });
}

export function porterCategories(payloadWp: listResponseWp): Category[] {
    return payloadWp.map((p) => ({
        id: p.id,
        title: p?.title?.rendered || p.name || '',
        slug: p.slug || '',
    }));
}

async function fetchWpList(path: string, data: Record<string, string | number> = {}): Promise<listResponseWp> {
    const api = process.env?.API;
    if (!api) return [];

    const full_path = new URL(`${api}/wp-json/wp/v2${path}`);
    full_path.search = new URLSearchParams(
        Object.fromEntries(Object.entries(data).map(([key, value]) => [key, String(value)])),
    ).toString();

    try {
        const response = await fetch(full_path, { next: { revalidate: 60 } });
        if (!response.ok) return [];

        const text = await response.text();
        if (!text.trim()) return [];

        const payload = JSON.parse(text);
        if (!Array.isArray(payload)) return [];

        return payload;
    } catch {
        return [];
    }
}

export async function GetApi(path: string, data: Record<string, string | number> = {}): Promise<Projects> {
    const projects = porter(await fetchWpList(path, data));
    // Pedido por slug pode ser o gêmeo PT; listagens só usam o canônico EN.
    if (data.slug) return projects;
    return excludeProjectTranslationTwins(projects);
}

export async function GetCategoriesApi(
    path: string,
    data: Record<string, string | number> = {},
): Promise<Category[]> {
    return porterCategories(await fetchWpList(path, data));
}

/** Projetos filtrados por slug da taxonomia `project-category` (ex.: `education`). */
export async function GetProjectsByCategorySlug(
    slug: string,
    data: Record<string, string | number> = {},
): Promise<Projects> {
    const terms = await fetchWpList('/project-category', { slug, per_page: 1, ...data });
    const termId = terms[0]?.id;
    if (!termId) return [];

    return GetApi('/project', {
        'project-category': termId,
        _embed: '',
        per_page: 100,
        ...data,
    });
}

export async function GetMeta(): Promise<ProjectMeta[]> {
    let full_path = `${process.env?.API}/wp-json/api-etc/v2/all-posts?v=1.1.1`;

    const response = await fetch(full_path, { cache: 'no-store' });
    if (!response.ok) return [];

    const payload = await response.json();
    if (!Array.isArray(payload)) return [];

    return payload.map((item: {
        slug?: string;
        img_secondary?: { url?: string };
        img_single?: { url?: string };
        img_primary?: { url?: string };
        video?: { url?: string };
        project_data?: ProjectStructuredData | null;
        [key: string]: unknown;
    }): ProjectMeta => ({
        slug: typeof item.slug === 'string' ? item.slug : '',
        img_secondary: item.img_secondary?.url
            ? { ...item.img_secondary, url: wpMediaUrl(item.img_secondary.url) }
            : item.img_secondary,
        img_single: item.img_single?.url
            ? { ...item.img_single, url: wpMediaUrl(item.img_single.url) }
            : item.img_single,
        img_primary: item.img_primary?.url
            ? { ...item.img_primary, url: wpMediaUrl(item.img_primary.url) }
            : item.img_primary,
        video: item.video?.url
            ? { ...item.video, url: wpMediaUrl(item.video.url) }
            : item.video,
        project_data: item.project_data
            ? {
                ...item.project_data,
                heroImage: item.project_data.heroImage?.url
                    ? { ...item.project_data.heroImage, url: wpMediaUrl(String(item.project_data.heroImage.url)) }
                    : item.project_data.heroImage,
                logoImage: item.project_data.logoImage?.url
                    ? { ...item.project_data.logoImage, url: wpMediaUrl(String(item.project_data.logoImage.url)) }
                    : item.project_data.logoImage,
                gallery: normalizeGalleryItems(item.project_data.gallery),
            }
            : item.project_data,
    }));
}

function featuredImageUrl(item: responseWp): string | undefined {
    const media = item._embedded?.['wp:featuredmedia']?.[0];
    if (!media) return undefined;

    const sizes = media.media_details?.sizes;
    return wpMediaUrl(sizes?.full?.source_url ?? sizes?.medium?.source_url ?? (media as { source_url?: string }).source_url);
}

function brandName(item: responseWp): string {
    const title = item?.title?.rendered?.trim();
    if (title) return title;

    const media = item._embedded?.['wp:featuredmedia']?.[0];
    const alt = media?.alt_text?.trim();
    if (alt) return alt;

    const mediaTitle = media?.title?.rendered?.trim();
    if (mediaTitle) return mediaTitle;

    return item.slug;
}

export function porterBrands(payloadWp: listResponseWp): Brand[] {
    return payloadWp.map((item) => ({
        id: item.id,
        name: brandName(item),
        slug: item.slug,
        logo: featuredImageUrl(item),
        link: item.link,
    }));
}

export async function GetBrandsApi(data: Record<string, string> = {}) {
    const api = process.env?.API;
    if (!api) return [];

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/brand`);
        fullPath.search = new URLSearchParams(data).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return [];

        const payload = await response.json();
        if (!Array.isArray(payload)) return [];

        return porterBrands(payload);
    } catch {
        return [];
    }
}

export function porterIntro(payloadWp: listResponseWp): Intro | null {
    const item = payloadWp[0];
    if (!item) return null;

    const fromRest = item.intro_data;
    if (fromRest?.headline?.trim()) {
        return {
            headline: fromRest.headline.trim(),
            body: fromRest.body?.trim() ?? '',
        };
    }

    const headline = item.content?.rendered?.trim();
    if (!headline) return null;

    return {
        headline,
        body: item.excerpt?.rendered?.trim() ?? '',
    };
}

export async function GetIntroApi(data: Record<string, string> = {}): Promise<Intro | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/intro`);
        fullPath.search = new URLSearchParams({
            per_page: '1',
            slug: data.slug ?? 'en',
            ...data,
        }).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        if (!Array.isArray(payload)) return null;

        return porterIntro(payload);
    } catch {
        return null;
    }
}

function decodeFooterJson(raw: string): unknown {
    const stripped = raw
        .replace(/<[^>]+>/g, '')
        .replace(/&quot;/g, '"')
        .replace(/&#039;/g, "'")
        .replace(/&apos;/g, "'")
        .replace(/&#038;/g, '&')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&#91;/g, '[')
        .replace(/&#93;/g, ']')
        .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number(code)))
        .replace(/&#x([0-9a-f]+);/gi, (_, code) => String.fromCharCode(parseInt(code, 16)))
        .trim();

    return JSON.parse(stripped);
}

function isFooterLink(value: unknown): value is FooterContent['links'][number] {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    return typeof item.title === 'string' && typeof item.subtitle === 'string' && typeof item.href === 'string';
}

function isFooterContent(value: unknown): value is FooterContent {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    const legal = item.legal as Record<string, unknown> | undefined;
    const links = item.links;

    if (typeof item.brandMark !== 'string') return false;
    if (!Array.isArray(links) || links.length === 0 || !links.every(isFooterLink)) return false;
    if (!legal) return false;

    return (
        typeof legal.brand === 'string' &&
        typeof legal.privacy === 'string' &&
        typeof legal.cookies === 'string' &&
        (legal.privacyHref === undefined || typeof legal.privacyHref === 'string') &&
        (legal.cookiesHref === undefined || typeof legal.cookiesHref === 'string') &&
        (legal.privacyBody === undefined || typeof legal.privacyBody === 'string') &&
        (legal.cookiesBody === undefined || typeof legal.cookiesBody === 'string')
    );
}

export function porterFooter(payloadWp: listResponseWp): FooterContent | null {
    const item = payloadWp[0];
    if (!item) return null;

    if (item.footer_data && isFooterContent(item.footer_data)) {
        const links = item.footer_data.links.filter((link) => link.title && link.href);
        if (links.length > 0) {
            return { ...item.footer_data, links };
        }
    }

    const raw = item.content?.rendered?.trim();
    if (!raw) return null;

    try {
        const parsed = decodeFooterJson(raw);
        return isFooterContent(parsed) ? parsed : null;
    } catch {
        return null;
    }
}

export async function GetIntroByLocale(locale: WpLocale): Promise<Intro | null> {
    return GetIntroApi({ slug: wpLangSlug(locale) });
}

export async function GetFooterApi(data: Record<string, string> = {}): Promise<FooterContent | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/footer`);
        fullPath.search = new URLSearchParams({
            per_page: '1',
            slug: data.slug ?? 'en',
            ...data,
        }).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        if (!Array.isArray(payload)) return null;

        return porterFooter(payload);
    } catch {
        return null;
    }
}

export async function GetFooterByLocale(locale: WpLocale): Promise<FooterContent | null> {
    return GetFooterApi({ slug: wpLangSlug(locale) });
}

function isCapabilitySection(value: unknown): value is CapabilitiesContent['sections'][number] {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    return typeof item.title === 'string' && typeof item.body === 'string';
}

function isCapabilitiesContent(value: unknown): value is CapabilitiesContent {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    if (typeof item.headline !== 'string') return false;
    if (!Array.isArray(item.sections)) return false;
    return item.sections.every(isCapabilitySection);
}

export function porterCapabilities(payloadWp: listResponseWp): CapabilitiesContent | null {
    const item = payloadWp[0];
    if (!item) return null;

    if (item.capabilities_data && isCapabilitiesContent(item.capabilities_data)) {
        const sections = item.capabilities_data.sections.filter((section) => section.title);
        if (item.capabilities_data.headline || sections.length > 0) {
            return {
                headline: item.capabilities_data.headline,
                sections,
            };
        }
    }

    return null;
}

export async function GetCapabilitiesApi(data: Record<string, string> = {}): Promise<CapabilitiesContent | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/capabilities`);
        fullPath.search = new URLSearchParams({
            per_page: '1',
            slug: data.slug ?? 'en',
            ...data,
        }).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        if (!Array.isArray(payload)) return null;

        return porterCapabilities(payload);
    } catch {
        return null;
    }
}

export async function GetCapabilitiesByLocale(locale: WpLocale): Promise<CapabilitiesContent | null> {
    return GetCapabilitiesApi({ slug: wpLangSlug(locale) });
}

function isAboutAccordionSection(value: unknown): value is AboutContent['accordionSections'][number] {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    return typeof item.title === 'string' && typeof item.body === 'string';
}

function isAboutContent(value: unknown): value is AboutContent {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    if (typeof item.headline !== 'string') return false;
    if (typeof item.body !== 'string') return false;
    if (!Array.isArray(item.accordionSections)) return false;
    return item.accordionSections.every(isAboutAccordionSection);
}

export function porterAbout(payloadWp: listResponseWp): AboutContent | null {
    const item = payloadWp[0];
    if (!item?.about_data || !isAboutContent(item.about_data)) return null;

    const data = item.about_data;
    const sections = data.accordionSections.filter((section) => section.title);

    if (!data.headline && !data.body && !data.heroImage && !data.heroVideo && sections.length === 0) {
        return null;
    }

    return {
        heroImage: typeof data.heroImage === 'string' ? data.heroImage : undefined,
        heroVideo: typeof data.heroVideo === 'string' ? data.heroVideo : undefined,
        headline: data.headline,
        body: data.body,
        accordionSections: sections,
    };
}

export async function GetAboutApi(data: Record<string, string> = {}): Promise<AboutContent | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/about`);
        fullPath.search = new URLSearchParams({
            per_page: '1',
            slug: data.slug ?? 'en',
            ...data,
        }).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        if (!Array.isArray(payload)) return null;

        return porterAbout(payload);
    } catch {
        return null;
    }
}

export async function GetAboutByLocale(locale: WpLocale): Promise<AboutContent | null> {
    return GetAboutApi({ slug: wpLangSlug(locale) });
}

function isEducationAccordionSection(value: unknown): value is EducationContent['accordionSections'][number] {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    return typeof item.title === 'string' && typeof item.body === 'string';
}

function isEducationContent(value: unknown): value is EducationContent {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    if (typeof item.headline !== 'string') return false;
    if (!Array.isArray(item.accordionSections)) return false;
    if (!item.accordionSections.every(isEducationAccordionSection)) return false;
    if (item.institutions !== undefined && !Array.isArray(item.institutions)) return false;
    if (item.heroVideo !== undefined && typeof item.heroVideo !== 'string') return false;
    return true;
}

export function porterEducation(payloadWp: listResponseWp): EducationContent | null {
    const item = payloadWp[0];
    if (!item?.education_data || !isEducationContent(item.education_data)) return null;

    const data = item.education_data;
    const sections = data.accordionSections.filter((section) => section.title);

    if (
        !data.headline &&
        !data.heroImage &&
        !data.heroVideo &&
        sections.length === 0 &&
        !(data.institutions?.length)
    ) {
        return null;
    }

    return {
		heroImage: typeof data.heroImage === 'string' ? data.heroImage : undefined,
		heroVideo: typeof data.heroVideo === 'string' ? data.heroVideo : undefined,
		headline: data.headline,
		accordionSections: sections,
		institutions: Array.isArray(data.institutions) ? data.institutions : [],
	};
}

export async function GetEducationApi(data: Record<string, string> = {}): Promise<EducationContent | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/education`);
        fullPath.search = new URLSearchParams({
            per_page: '1',
            slug: data.slug ?? 'en',
            ...data,
        }).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        if (!Array.isArray(payload)) return null;

        return porterEducation(payload);
    } catch {
        return null;
    }
}

export async function GetEducationByLocale(locale: WpLocale): Promise<EducationContent | null> {
    return GetEducationApi({ slug: wpLangSlug(locale) });
}

function isContactBlock(value: unknown): value is ContactContent['blocks'][number] {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    return typeof item.title === 'string' && typeof item.body === 'string';
}

function isContactContent(value: unknown): value is ContactContent {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    if (typeof item.headline !== 'string') return false;
    if (!Array.isArray(item.blocks)) return false;
    return item.blocks.every(isContactBlock);
}

export function porterContact(payloadWp: listResponseWp): ContactContent | null {
    const item = payloadWp[0];
    if (!item?.contact_data || !isContactContent(item.contact_data)) return null;

    const data = item.contact_data;
    const blocks = data.blocks.filter((block) => block.title);

    if (!data.headline && !data.heroImage && !data.heroVideo && blocks.length === 0) {
        return null;
    }

    return {
        heroImage: typeof data.heroImage === 'string' ? data.heroImage : undefined,
        heroVideo: typeof data.heroVideo === 'string' ? data.heroVideo : undefined,
        headline: data.headline,
        blocks,
    };
}

export async function GetContactApi(data: Record<string, string> = {}): Promise<ContactContent | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/contact`);
        fullPath.search = new URLSearchParams({
            per_page: '1',
            slug: data.slug ?? 'en',
            ...data,
        }).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        if (!Array.isArray(payload)) return null;

        return porterContact(payload);
    } catch {
        return null;
    }
}

export async function GetContactByLocale(locale: WpLocale): Promise<ContactContent | null> {
    return GetContactApi({ slug: wpLangSlug(locale) });
}

export function porterLegal(payloadWp: listResponseWp): LegalContent | null {
    const item = payloadWp[0];
    if (!item?.legal_data || typeof item.legal_data !== 'object') return null;
    const locale = item.slug === 'pt' ? 'pt' : 'en';
    return buildLegalContent(item.legal_data as LegalContent, locale);
}

export async function GetLegalApi(data: Record<string, string> = {}): Promise<LegalContent | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/legal`);
        fullPath.search = new URLSearchParams({
            per_page: '1',
            slug: data.slug ?? 'en',
            ...data,
        }).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        if (!Array.isArray(payload)) return null;

        return porterLegal(payload);
    } catch {
        return null;
    }
}

export async function GetLegalByLocale(locale: WpLocale): Promise<LegalContent> {
    const fromWp = await GetLegalApi({ slug: wpLangSlug(locale) });
    return buildLegalContent(fromWp, locale === 'pt' ? 'pt' : 'en');
}

function isProjectsPageContent(value: unknown): value is ProjectsPageContent {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    return (
        typeof item.title === 'string' &&
        typeof item.headline === 'string' &&
        typeof item.emptyMessage === 'string'
    );
}

export function porterProjectsPage(payloadWp: listResponseWp): ProjectsPageContent | null {
    const item = payloadWp[0];
    if (!item?.projects_page_data || !isProjectsPageContent(item.projects_page_data)) return null;

    const data = item.projects_page_data;
    if (!data.title && !data.headline && !data.emptyMessage) return null;

    return data;
}

export async function GetProjectsPageApi(data: Record<string, string> = {}): Promise<ProjectsPageContent | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/projects-page`);
        fullPath.search = new URLSearchParams({
            per_page: '1',
            slug: data.slug ?? 'en',
            ...data,
        }).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        if (!Array.isArray(payload)) return null;

        return porterProjectsPage(payload);
    } catch {
        return null;
    }
}

export async function GetProjectsPageByLocale(locale: WpLocale): Promise<ProjectsPageContent | null> {
    return GetProjectsPageApi({ slug: wpLangSlug(locale) });
}

function isSiteUiLabels(value: unknown): value is SiteUiContent['en']['labels'] {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    return typeof item.selectedProjects === 'string';
}

function isSiteUiNavLink(value: unknown): value is SiteUiContent['en']['nav'][number] {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    return typeof item.label === 'string' && typeof item.href === 'string';
}

function isSiteUiLocale(value: unknown): value is SiteUiContent['en'] {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    if (!isSiteUiLabels(item.labels)) return false;
    if (item.nav === undefined) return true;
    if (!Array.isArray(item.nav)) return false;
    return item.nav.every(isSiteUiNavLink);
}

function porterSiteUiLabelsPayload(data: unknown): SiteUiContent | null {
    if (!data || typeof data !== 'object') return null;
    const item = data as Record<string, unknown>;
    if (!isSiteUiLocale(item.en) || !isSiteUiLocale(item.pt)) return null;

    const layoutRaw = item.layout && typeof item.layout === 'object'
        ? (item.layout as Record<string, unknown>)
        : null;

    return {
        en: {
            labels: item.en.labels,
            nav: Array.isArray(item.en.nav) ? item.en.nav.filter(isSiteUiNavLink) : [],
        },
        pt: {
            labels: item.pt.labels,
            nav: Array.isArray(item.pt.nav) ? item.pt.nav.filter(isSiteUiNavLink) : [],
        },
        layout: layoutRaw
            ? {
                homeColumns: Number(layoutRaw.homeColumns) as 1 | 2 | 3,
                projectsInitialCount: Number(layoutRaw.projectsInitialCount),
                latestCount: Number(layoutRaw.latestCount),
            }
            : undefined,
    };
}

export function porterSiteUi(payloadWp: listResponseWp): SiteUiContent | null {
    const item = payloadWp[0];
    if (!item?.site_ui_data) return null;
    return porterSiteUiLabelsPayload(item.site_ui_data);
}

export function porterHeaderNav(value: unknown): HeaderNavContent | null {
    if (!value || typeof value !== 'object') return null;
    const item = value as Record<string, unknown>;
    if (!Array.isArray(item.en) || !Array.isArray(item.pt)) return null;
    if (!item.en.every(isSiteUiNavLink) || !item.pt.every(isSiteUiNavLink)) return null;
    return { en: item.en, pt: item.pt };
}

export async function GetHeaderNavApi(): Promise<HeaderNavContent | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const response = await fetch(`${api}/wp-json/rs/v1/header-nav`, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        return porterHeaderNav(payload);
    } catch {
        return null;
    }
}

export async function GetSiteUiApi(data: Record<string, string> = {}): Promise<SiteUiContent | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/site-ui`);
        fullPath.search = new URLSearchParams({
            per_page: '1',
            slug: data.slug ?? 'en',
            ...data,
        }).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        if (!Array.isArray(payload)) return null;

        return porterSiteUi(payload);
    } catch {
        return null;
    }
}

function isBlobVisual(value: unknown): value is BlobVisual {
    if (!value || typeof value !== 'object') return false;
    const item = value as Record<string, unknown>;
    return (
        typeof item.color1 === 'string' &&
        typeof item.color2 === 'string' &&
        Array.isArray(item.palette) &&
        item.palette.every((color) => typeof color === 'string')
    );
}

export function porterBlobVisual(value: unknown): BlobVisual | null {
    return isBlobVisual(value) ? value : null;
}

export async function GetBlobVisualApi(): Promise<BlobVisual | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const response = await fetch(`${api}/wp-json/rs/v1/blob-visual`, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        return porterBlobVisual(payload);
    } catch {
        return null;
    }
}

