import type { NextApiRequest, NextApiResponse } from 'next'
import { GetApi, data, ListPost } from '../../components/ApiWp'

export default async function handler(
	req: NextApiRequest,
	res: NextApiResponse<ListPost>
) {
	
	let slug = `${req.query?.slug}`
	let query: data = {
		slug,
		_embed: '',
		translate: 'PT',
	}
	let apiWp = await GetApi('/pages', query)
	res.status(200).json(apiWp)
}