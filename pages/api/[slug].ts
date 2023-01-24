import type { NextApiRequest, NextApiResponse } from 'next'
import { GetApi, data, ListPost } from '../../components/ApiWp'

export default async function handler(
	req: NextApiRequest,
	res: NextApiResponse<ListPost>
) {
	const language = req.cookies['language'] || ''
	let slug = `${req.query?.slug}`
	let query: data = {
		slug,
		_embed: '',
		translate: language	
	}
	let apiWp = await GetApi('/pages', query)
	res.status(200).json(apiWp)
}