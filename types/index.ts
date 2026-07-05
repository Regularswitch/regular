export type Projects = Array<Project>

export interface Category {
  id: number;
  title: string;
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

export type ProjectStructuredImage = {
  url?: string | false;
  width?: number;
  height?: number;
};

export type ProjectStructuredData = {
  heroImage?: ProjectStructuredImage | null;
  logoImage?: ProjectStructuredImage | null;
  accordion?: Array<{ index: number; body: string }>;
  gallery?: string[];
};

export type ProjectMeta = {
  slug: string;
  img_single?: { url?: string | false };
  img_secondary?: { url?: string | false };
  img_primary?: { url?: string | false };
  video?: { url?: string | false };
  project_data?: ProjectStructuredData | null;
};