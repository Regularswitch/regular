
import type { NextApiRequest, NextApiResponse } from 'next'
import { GetApi, data, ListPost } from '../../../components/ApiWp'

export default async function handler(
	req: NextApiRequest,
	res: NextApiResponse<ListPost>
) {
	let query: data = {
		per_page: 22,
		translate: 'EN',
	}
	let apiWp = await GetApi('/project-category', query)
	res.status(200).json(apiWp.map(p => ({ ...p, content: "" })))
}