import type { Projects } from '../../types';

export function sortProjectsByDate(projects: Projects) {
	return [...projects].sort(
		(a, b) => new Date(b.created_at as Date).getTime() - new Date(a.created_at as Date).getTime(),
	);
}
