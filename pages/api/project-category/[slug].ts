import type { NextApiRequest, NextApiResponse } from 'next'
import { GetApi, data, ListPost } from '../../../components/ApiWp'

export default async function handler(
	req: NextApiRequest,
	res: NextApiResponse<ListPost>
) {
	const language = (req.cookies['language']) || 'PT'
	let slug = `${req.query?.slug}`
	let query: data = {
		categories: slug,
		_embed: '',
		per_page: 100,
		translate: language,
	}
	let apiWp = await GetApi('/project', query)
	res.status(200).json(apiWp)
}