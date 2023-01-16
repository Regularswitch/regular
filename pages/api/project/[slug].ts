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
		slug,
		_embed: ''
	}
	let apiWp = await GetApi('/project', query, lang)
	res.status(200).json(apiWp)
}