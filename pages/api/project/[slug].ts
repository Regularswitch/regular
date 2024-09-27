import type { NextApiRequest, NextApiResponse } from 'next';
import { GetApi, data, GetMeta } from '../../../components/ApiWp';
import { Projects } from '../../../types';

export default async function handler(
	req: NextApiRequest,
	res: NextApiResponse<Projects>
) {
	const language = req.cookies['language'] || ''
	
	let slug = `${req.query?.slug}`
	let query: data = {
		slug,
		_embed: '',	
		translate: language,
		meta: '1'
	}
	let apiWp = await GetApi('/project/', query)
	res.status(200).json(apiWp)
}