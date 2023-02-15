
import type { NextApiRequest, NextApiResponse } from 'next'
import { GetMeta } from '../../../components/ApiWp'

export default async function handler(
	req: NextApiRequest,
	res: NextApiResponse
) {
	let apiWp = await GetMeta()
	res.status(200).json(apiWp)
}