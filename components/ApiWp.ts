import { tipoLinguagens } from "./Language"
import { type Brand, type FooterContent, type Intro, type ProjectStructuredData, type Projects } from '../types';
import { wpMediaUrl } from '../lib/wpMediaUrl';

export type data = {
    translate?: tipoLinguagens | string
    _links?: string
    _embed?: string
    slug?: string
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
    project_data?: ProjectStructuredData
    meta?: Record<string, string>
}

export type listResponseWp = Array<responseWp>

export function porter(payloadWp: listResponseWp): Projects {
    return payloadWp.map(p => ({
        id: p.id,
        title: p?.title?.rendered || p.name,
        slug: p.slug,
        link: p.link,
        image_medium: wpMediaUrl(p._embedded?.["wp:featuredmedia"]?.[0]?.media_details?.sizes?.medium?.source_url),
        image_full: wpMediaUrl(p._embedded?.["wp:featuredmedia"]?.[0]?.media_details?.sizes.full?.source_url),
        content: p?.content?.rendered ,
        more: p?.excerpt?.rendered,
        category: p["project-category"],
        description: p.description,
        created_at: p.date,
        image: wpMediaUrl(p?._links?.["wp:attachment"]?.[0]?.href),
        project_data: p.project_data ?? null,
    }))
}

export async function GetApi(path: string, data: any) {
    const BASE = `${process.env?.API}/wp-json/wp/v2`;
    let full_path = new URL(`${BASE}${path}`);
    full_path.search = new URLSearchParams(data).toString();

    return porter(await (await fetch(full_path)).json());
}

export async function GetMeta() {
    let full_path = `${process.env?.API}/wp-json/api-etc/v2/all-posts?v=1.1.1`;

    const response = await fetch(full_path, { cache: 'no-store' });
    if (!response.ok) return [];

    const payload = await response.json();
    if (!Array.isArray(payload)) return [];

    return payload.map((item: { img_secondary?: { url?: string }; img_single?: { url?: string }; img_primary?: { url?: string }; project_data?: ProjectStructuredData | null; [key: string]: unknown }) => ({
        ...item,
        img_secondary: item.img_secondary?.url
            ? { ...item.img_secondary, url: wpMediaUrl(item.img_secondary.url) }
            : item.img_secondary,
        img_single: item.img_single?.url
            ? { ...item.img_single, url: wpMediaUrl(item.img_single.url) }
            : item.img_single,
        img_primary: item.img_primary?.url
            ? { ...item.img_primary, url: wpMediaUrl(item.img_primary.url) }
            : item.img_primary,
        project_data: item.project_data
            ? {
                ...item.project_data,
                heroImage: item.project_data.heroImage?.url
                    ? { ...item.project_data.heroImage, url: wpMediaUrl(String(item.project_data.heroImage.url)) }
                    : item.project_data.heroImage,
                logoImage: item.project_data.logoImage?.url
                    ? { ...item.project_data.logoImage, url: wpMediaUrl(String(item.project_data.logoImage.url)) }
                    : item.project_data.logoImage,
                gallery: item.project_data.gallery?.map((url) => wpMediaUrl(url) ?? url),
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
        fullPath.search = new URLSearchParams({ per_page: '1', ...data }).toString();

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
        typeof legal.privacyHref === 'string' &&
        typeof legal.cookies === 'string' &&
        typeof legal.cookiesHref === 'string'
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

export async function GetFooterApi(data: Record<string, string> = {}): Promise<FooterContent | null> {
    const api = process.env?.API;
    if (!api) return null;

    try {
        const fullPath = new URL(`${api}/wp-json/wp/v2/footer`);
        fullPath.search = new URLSearchParams({ per_page: '1', ...data }).toString();

        const response = await fetch(fullPath, { cache: 'no-store' });
        if (!response.ok) return null;

        const payload = await response.json();
        if (!Array.isArray(payload)) return null;

        return porterFooter(payload);
    } catch {
        return null;
    }
}

