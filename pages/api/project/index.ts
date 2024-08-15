import type { NextApiRequest, NextApiResponse } from 'next'
import { Projects } from '../../../types'
import { GetApi, data } from '../../../components/ApiWp'

export default async function handler(
	req: NextApiRequest,
	res: NextApiResponse<Projects>
) {
	const language = req.cookies['language'] || ''
	let query: data = {
		_embed: '',
		per_page: 100,
		translate: language	
	}
	let apiWp = await GetApi('/project/', query)
	res.status(200).json(apiWp.map(p => ({ ...p, content: "" })))
}