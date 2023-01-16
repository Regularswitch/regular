import type { NextApiRequest, NextApiResponse } from 'next'
import Language from "../../../components/Language"
import { GetApi, data, ListPost } from '../../../components/ApiWp'

export default async function handler(
	req: NextApiRequest,
	res: NextApiResponse<ListPost>
) {
	let slug = `${req.query?.slug}`
	let lang = Language(req)
	let query: data = {
		categories: slug,
		_embed: '',
		per_page: 100
	}
	let apiWp = await GetApi('/project', query, lang)
	res.status(200).json(apiWp)
}