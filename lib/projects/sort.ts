import type { Projects } from '../../types';

export function sortProjectsByDate(projects: Projects) {
	return [...projects].sort(
		(a, b) => new Date(b.created_at as Date).getTime() - new Date(a.created_at as Date).getTime(),
	);
}

/**
 * Gêmeos PT no WP usam slug `*-pt` e não devem aparecer na listagem
 * (o canônico EN é listado; ?translate=PT troca o conteúdo).
 */
export function excludeProjectTranslationTwins(projects: Projects): Projects {
	return projects.filter((project) => !/-pt$/i.test(project.slug));
}
