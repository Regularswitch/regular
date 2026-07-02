import { tipoLinguagens } from "./Language"
import { type Brand, type Intro, type Projects } from '../types';

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
}

export type listResponseWp = Array<responseWp>

export function porter(payloadWp: listResponseWp): Projects {
    return payloadWp.map(p => ({
        id: p.id,
        title: p?.title?.rendered || p.name,
        slug: p.slug,
        link: p.link,
        image_medium: p._embedded?.["wp:featuredmedia"]?.[0]?.media_details?.sizes?.medium?.source_url,
        image_full: p._embedded?.["wp:featuredmedia"]?.[0]?.media_details?.sizes.full?.source_url,
        content: p?.content?.rendered ,
        more: p?.excerpt?.rendered,
        category: p["project-category"],
        description: p.description,
        created_at: p.date,
        image: p?._links?.["wp:attachment"]?.[0]?.href,
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
    return Array.isArray(payload) ? payload : [];
}

function featuredImageUrl(item: responseWp): string | undefined {
    const media = item._embedded?.['wp:featuredmedia']?.[0];
    if (!media) return undefined;

    const sizes = media.media_details?.sizes;
    return sizes?.full?.source_url ?? sizes?.medium?.source_url ?? (media as { source_url?: string }).source_url;
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

