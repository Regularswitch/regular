/**
 * Captura prints do WP Admin (Regular CMS) para a documentação ilustrada.
 *
 * Uso:
 *   cp .env.example .env   # preencha WP_USER / WP_PASS
 *   npm install
 *   npm run capture:install
 *   npm run capture
 *
 * Saída: ./screenshots/*.png
 */

import { chromium } from 'playwright';
import { mkdir, writeFile, access } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const OUT = join(__dirname, 'screenshots');

async function loadEnv() {
	const envPath = join(__dirname, '.env');
	try {
		await access(envPath);
		const raw = await import('node:fs').then((fs) =>
			fs.readFileSync(envPath, 'utf8'),
		);
		for (const line of raw.split('\n')) {
			const t = line.trim();
			if (!t || t.startsWith('#')) continue;
			const i = t.indexOf('=');
			if (i < 0) continue;
			const key = t.slice(0, i).trim();
			let val = t.slice(i + 1).trim();
			if (
				(val.startsWith('"') && val.endsWith('"')) ||
				(val.startsWith("'") && val.endsWith("'"))
			) {
				val = val.slice(1, -1);
			}
			if (!process.env[key]) process.env[key] = val;
		}
	} catch {
		/* .env opcional se as vars já estiverem no ambiente */
	}
}

function requireEnv(name) {
	const v = (process.env[name] || '').trim();
	if (!v) {
		console.error(`Falta ${name}. Configure docs/admin/.env (veja .env.example).`);
		process.exit(1);
	}
	return v;
}

function adminUrl(path) {
	const base = (process.env.WP_ADMIN_URL || 'http://regularswitch-wp.local').replace(
		/\/$/,
		'',
	);
	const p = path.startsWith('/') ? path : `/${path}`;
	return `${base}${p}`;
}

async function shot(page, name, fullPage = true) {
	const file = join(OUT, `${name}.png`);
	await page.screenshot({ path: file, fullPage });
	console.log('✓', name);
}

async function login(page) {
	const user = requireEnv('WP_USER');
	const pass = requireEnv('WP_PASS');
	await page.goto(adminUrl('/wp-login.php'), { waitUntil: 'domcontentloaded' });
	await page.fill('#user_login', user);
	await page.fill('#user_pass', pass);
	await Promise.all([
		page.waitForURL(/\/wp-admin/),
		page.click('#wp-submit'),
	]);
	await page.waitForLoadState('networkidle').catch(() => {});
}

async function firstEditLink(page, postType) {
	await page.goto(adminUrl(`/wp-admin/edit.php?post_type=${postType}`), {
		waitUntil: 'domcontentloaded',
	});
	const link = page.locator('table.wp-list-table tbody .row-title').first();
	if ((await link.count()) === 0) return null;
	return link.getAttribute('href');
}

async function gotoEdit(page, postType, envKey) {
	const forced = (process.env[envKey] || '').trim();
	if (forced) {
		await page.goto(adminUrl(`/wp-admin/post.php?post=${forced}&action=edit`), {
			waitUntil: 'domcontentloaded',
		});
		return true;
	}
	const href = await firstEditLink(page, postType);
	if (!href) {
		console.warn(`⚠ Nenhum post de ${postType} — pulando.`);
		return false;
	}
	await page.goto(href, { waitUntil: 'domcontentloaded' });
	return true;
}

async function setTheme(page, theme) {
	/* light | dark | system — via admin bar */
	await page.evaluate((value) => {
		const root = document.documentElement;
		root.classList.remove('rs-theme-light', 'rs-theme-dark');
		if (value === 'dark' || value === 'light') {
			root.classList.add('rs-theme-' + value);
			root.setAttribute('data-rs-theme', value);
			root.setAttribute('data-rs-theme-pref', value);
			try {
				localStorage.setItem('rs_admin_theme', value);
			} catch (_) {}
		}
		document.body?.classList.remove('rs-theme-light', 'rs-theme-dark');
		document.body?.classList.add('rs-theme-' + (value === 'dark' ? 'dark' : 'light'));
	}, theme);
	await page.waitForTimeout(200);
}

async function main() {
	await loadEnv();
	await mkdir(OUT, { recursive: true });

	const browser = await chromium.launch({ headless: true });
	const context = await browser.newContext({
		viewport: { width: 1440, height: 900 },
		deviceScaleFactor: 1,
	});
	const page = await context.newPage();

	console.log('Login…');
	await login(page);
	await setTheme(page, 'light');

	/* 01 — Dashboard */
	await page.goto(adminUrl('/wp-admin/index.php'), { waitUntil: 'domcontentloaded' });
	await shot(page, '01-dashboard');

	/* 02 — Hub Conteúdo */
	await page.goto(adminUrl('/wp-admin/admin.php?page=rs-content'), {
		waitUntil: 'domcontentloaded',
	});
	await shot(page, '02-hub-conteudo');

	/* 03 — Hub Sistema */
	await page.goto(adminUrl('/wp-admin/admin.php?page=rs-system'), {
		waitUntil: 'domcontentloaded',
	});
	await shot(page, '03-hub-sistema');

	/* 04 — Design System light */
	await page.goto(adminUrl('/wp-admin/admin.php?page=rs-design-system'), {
		waitUntil: 'domcontentloaded',
	});
	await setTheme(page, 'light');
	await shot(page, '04-design-system-light');

	/* 05 — Design System dark */
	await setTheme(page, 'dark');
	await shot(page, '05-design-system-dark');
	await setTheme(page, 'light');

	/* 06 — Intro */
	if (await gotoEdit(page, 'intro', 'WP_INTRO_ID')) {
		await page.waitForSelector('.rs-ds-editor, #rs_intro_fields, #post', {
			timeout: 15000,
		}).catch(() => {});
		await shot(page, '06-intro');
	}

	/* 07 — Capacidades */
	if (await gotoEdit(page, 'capabilities', 'WP_CAPABILITIES_ID')) {
		await page.waitForSelector('.rs-ds-editor, #rs_capabilities_fields', {
			timeout: 15000,
		}).catch(() => {});
		await shot(page, '07-capacidades');
	}

	/* 08 — Projeto */
	if (await gotoEdit(page, 'project', 'WP_PROJECT_ID')) {
		await page.waitForSelector('.rs-project-tabs, .rs-ds-editor', {
			timeout: 15000,
		}).catch(() => {});
		await shot(page, '08-projeto-geral');

		const mediaTab = page.locator('.rs-project-tab[data-tab="media"]');
		if ((await mediaTab.count()) > 0) {
			await mediaTab.click();
			await page.waitForTimeout(400);
			await shot(page, '09-projeto-midia');
		}
	}

	/* 10 — SEO (no mesmo projeto ou intro) */
	const seoBox = page.locator('#rs_seo_fields');
	if ((await seoBox.count()) > 0) {
		await seoBox.scrollIntoViewIfNeeded();
		await shot(page, '10-seo-metabox', false);
	} else if (await gotoEdit(page, 'intro', 'WP_INTRO_ID')) {
		const box = page.locator('#rs_seo_fields');
		if ((await box.count()) > 0) {
			await box.scrollIntoViewIfNeeded();
			await shot(page, '10-seo-metabox', false);
		}
	}

	/* 11 — Listagem projetos */
	await page.goto(adminUrl('/wp-admin/edit.php?post_type=project'), {
		waitUntil: 'domcontentloaded',
	});
	await shot(page, '11-listagem-projetos');

	/* 12 — Categorias */
	await page.goto(
		adminUrl('/wp-admin/edit-tags.php?taxonomy=project-category&post_type=project'),
		{ waitUntil: 'domcontentloaded' },
	);
	await shot(page, '12-categorias');

	/* Manifesto para o ADMIN.md */
	const manifest = {
		generatedAt: new Date().toISOString(),
		baseUrl: process.env.WP_ADMIN_URL,
		files: [
			'01-dashboard.png',
			'02-hub-conteudo.png',
			'03-hub-sistema.png',
			'04-design-system-light.png',
			'05-design-system-dark.png',
			'06-intro.png',
			'07-capacidades.png',
			'08-projeto-geral.png',
			'09-projeto-midia.png',
			'10-seo-metabox.png',
			'11-listagem-projetos.png',
			'12-categorias.png',
		],
	};
	await writeFile(join(OUT, 'manifest.json'), JSON.stringify(manifest, null, 2));

	await browser.close();
	console.log('\nPronto →', OUT);
	console.log('Depois atualize as imagens em ADMIN.md (já referenciadas).');
}

main().catch((err) => {
	console.error(err);
	process.exit(1);
});
