import { NextResponse } from 'next/server';

import { GetHeaderNavApi, GetSiteUiApi } from '../../../components/ApiWp';
import { buildSiteUiWithHeaderNav } from '../../../lib/resolveSiteUi';

export const dynamic = 'force-dynamic';

export async function GET() {
	const [siteUi, headerNav] = await Promise.all([GetSiteUiApi(), GetHeaderNavApi()]);
	return NextResponse.json(buildSiteUiWithHeaderNav(siteUi, headerNav));
}
