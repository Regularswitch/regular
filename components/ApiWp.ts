import { type } from "os"
import { tipoLinguagens } from "./Language"
import { Projects, Project } from '../types';

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

    return await (await fetch(full_path)).json();
}

