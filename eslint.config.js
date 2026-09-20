import next from '@next/eslint-plugin-next';
import js from '@eslint/js';
import tsParser from '@typescript-eslint/parser';
import tseslint from '@typescript-eslint/eslint-plugin';

export default [
	{
		ignores: ['.next/**', 'node_modules/**'],
	},
	js.configs.recommended,
	{
		files: ['**/*.{js,jsx,ts,tsx}'],
		languageOptions: {
			parser: tsParser,
			parserOptions: {
				ecmaVersion: 'latest',
				sourceType: 'module',
				ecmaFeatures: { jsx: true },
			},
			globals: {
				window: 'readonly',
				document: 'readonly',
				location: 'readonly',
				fetch: 'readonly',
				console: 'readonly',
				setTimeout: 'readonly',
				clearTimeout: 'readonly',
				process: 'readonly',
			},
		},
		plugins: {
			'@next/next': next,
			'@typescript-eslint': tseslint,
		},
		rules: {
			...next.configs['core-web-vitals'].rules,
			'no-undef': 'off',
			'no-unused-vars': 'off',
			'no-empty': 'off',
			'no-extra-boolean-cast': 'off',
		},
	},
	{
		files: ['**/*.config.js', 'next.config.js', 'postcss.config.js', 'tailwind.config.js'],
		languageOptions: {
			globals: {
				module: 'readonly',
				require: 'readonly',
				process: 'readonly',
			},
		},
	},
];

