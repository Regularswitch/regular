import { GetContactByLocale } from '../../components/ApiWp';
import { buildContactContent } from '../content/contact/build';
import { getDefaultContactContent } from '../content/contact/defaults';

export async function fetchContactPage(locale: 'en' | 'pt') {
	return fetchContactFromWp(locale).catch((error) => {
		console.error('Error fetching contact page', error);
		return {
			content: {
				...getDefaultContactContent(locale),
				heroImage: undefined,
				heroVideo: undefined,
			},
		};
	});
}

async function fetchContactFromWp(locale: 'en' | 'pt') {
	const contact = await GetContactByLocale(locale);

	return {
		content: buildContactContent(contact, locale),
	};
}
