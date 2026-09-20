import { NextResponse } from 'next/server';
import { GetMeta } from '../../../../components/ApiWp';

export async function GET() {
	const apiWp = await GetMeta();
	return NextResponse.json(apiWp);
}

