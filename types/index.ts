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
};