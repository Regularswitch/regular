import type { NextApiRequest, NextApiResponse } from 'next'
import Language from "../../../components/Language"
import { GetApi, data, ListPost } from '../../../components/ApiWp'

export default async function handler(
	req: NextApiRequest,
	res: NextApiResponse<ListPost>
) {
	let lang = Language(req)
	let query: data = {
		_embed: '',
		per_page: 22
	}
	let apiWp = await GetApi('/project/', query, lang)

	res.status(200).json(apiWp.map(p => ({ ...p, content: "" })))
}