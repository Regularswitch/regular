import { GetContactByLocale } from '../components/ApiWp';
import { buildContactContent } from './buildContactContent';
import { DEFAULT_CONTACT_HERO_IMAGE, getDefaultContactContent } from './contactDefaults';

export async function fetchContactPage(locale: 'en' | 'pt') {
	return fetchContactFromWp(locale).catch((error) => {
		console.error('Error fetching contact page', error);
		return {
			content: {
				...getDefaultContactContent(locale),
				heroImage: DEFAULT_CONTACT_HERO_IMAGE,
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
