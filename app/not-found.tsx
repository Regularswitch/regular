import NotFoundPage from '../components/NotFound/NotFoundPage';
import { GetBlobVisualApi } from '../components/ApiWp';
import { resolveBlobVisual } from '../lib/blobDefaults';

export default async function NotFound() {
	const blob = resolveBlobVisual(await GetBlobVisualApi());

	return <NotFoundPage blob={blob} />;
}
