import type { NextApiRequest, NextApiResponse } from 'next'
import { GetApi, data, ListPost } from '../../../components/ApiWp'

export default async function handler(
	req: NextApiRequest,
	res: NextApiResponse<ListPost>
) {
	const language = req.cookies['language'] || ''
	let query: data = {
		_embed: '',
		per_page: 22,
		translate: language	
	}
	let apiWp = await GetApi('/project/', query)
	res.status(200).json(apiWp.map(p => ({ ...p, content: "" })))
}