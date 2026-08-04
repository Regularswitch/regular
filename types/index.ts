export type Projects = Array<Project>

export interface Category {
  id: number;
  title: string;
  slug?: string;
}

export interface Meta {
  slug: string;
  img_secondary?: { url: string };
}

export interface HomeProps {
  projects: Projects;
  cats: Category[];
  allMetas: Meta[];
}

export interface IndexProps {
  projects: Projects;
  allCat: Category[];
  allMetas: Meta[];
}

export type Project = {
  id: number;
  title?: string;
  slug: string;
  name?: string;
  image_full?: string;
  image_medium?: string;
  content: string;
  link: string;
  more?: string;
  category?: number[];
  description?: string;
  created_at?: Date;
  project_data?: ProjectStructuredData | null;
};

export type Brands = Brand[];

export type Brand = {
  id: number;
  name: string;
  slug: string;
  logo?: string;
  link?: string;
};

export type Intro = {
  headline: string;
  body: string;
};

export type FooterLink = {
  title: string;
  subtitle: string;
  href: string;
  external?: boolean;
};

export type FooterLegal = {
  brand: string;
  privacy: string;
  privacyHref: string;
  cookies: string;
  cookiesHref: string;
};

export type FooterContent = {
  brandMark: string;
  links: FooterLink[];
  legal: FooterLegal;
};

export type CapabilitySection = {
  title: string;
  body: string;
  image?: string;
  /** Campos legados (defaults do código) */
  lead?: string;
  servicesTitle?: string;
  services?: string[];
  imageProjectSlug?: string;
};

export type CapabilitiesContent = {
  headline: string;
  sections: CapabilitySection[];
};

export type SiteUiLabels = {
  selectedProjects: string;
  latestProjects: string;
  brandsMarquee: string;
  seeMoreProjects: string;
  seeMoreWork: string;
  whatsNewLabel: string;
  whatsNewTitle: string;
  whatsNewSubtitle: string;
};

export type SiteUiNavLink = {
  label: string;
  href: string;
};

export type SiteUiLocale = {
  labels: SiteUiLabels;
  nav: SiteUiNavLink[];
};

/** Layout compartilhado (não depende de idioma). */
export type SiteUiLayout = {
  /** Colunas da grade na home (Selected Projects). */
  homeColumns: 1 | 2 | 3;
  /** Quantos projetos mostrar ao abrir /projects (antes do “see more”). */
  projectsInitialCount: number;
  /** Quantos cards no “The Latest” (grade). */
  latestCount: number;
};

export type SiteUiContent = {
  en: SiteUiLocale;
  pt: SiteUiLocale;
  layout?: SiteUiLayout;
};

export type BlobVisual = {
  color1: string;
  color2: string;
  palette: string[];
};

export type ProjectMediaType = 'image' | 'video' | 'gif';

export type ProjectStructuredImage = {
  url?: string | false;
  width?: number;
  height?: number;
  mime?: string;
  type?: ProjectMediaType;
};

export type ProjectGalleryImage = {
  url: string;
  width?: number;
  height?: number;
  mime?: string;
  type?: ProjectMediaType;
};

export type ProjectStructuredData = {
  heroImage?: ProjectStructuredImage | null;
  logoImage?: ProjectStructuredImage | null;
  accordion?: Array<{ index: number; title?: string; body: string }>;
  /** URLs (legado) ou objetos com dimensões para grid fluido. */
  gallery?: Array<string | ProjectGalleryImage>;
  /** Destaque único na home (apenas um projeto deve estar true). */
  featuredOnHome?: boolean;
  /** Exibe vignette/logo no canto inferior esquerdo do hero. */
  showVignette?: boolean;
};

export type ProjectMeta = {
  slug: string;
  img_single?: { url?: string | false };
  img_secondary?: { url?: string | false };
  img_primary?: { url?: string | false };
  video?: { url?: string | false };
  project_data?: ProjectStructuredData | null;
};