import { defineConfig } from 'vitepress';

const repo = 'https://github.com/MaksymVasylchuk/users-bulk-delete-with-preview';
const wporg = 'https://wordpress.org/plugins/users-bulk-delete-with-preview/';
const checkout = 'https://checkout.freemius.com/plugin/43896/plan/77316/';

export default defineConfig( {
	title: 'Users Bulk Delete With Preview',
	description: 'Documentation of the Users Bulk Delete With Preview plugin for WordPress and its Pro add-on.',
	base: '/users-bulk-delete-with-preview/',
	lang: 'en-US',
	cleanUrls: true,
	lastUpdated: true,
	head: [ [ 'meta', { name: 'theme-color', content: '#2271b1' } ] ],

	themeConfig: {
		nav: [
			{ text: 'Free plugin', link: '/free/', activeMatch: '^/free/' },
			{ text: 'Pro add-on', link: '/pro/', activeMatch: '^/pro/' },
			{
				text: 'Links',
				items: [
					{ text: 'Free plugin on WordPress.org', link: wporg },
					{ text: 'Buy or try Pro', link: checkout },
					{ text: 'Support forum (free plugin)', link: 'https://wordpress.org/support/plugin/users-bulk-delete-with-preview/' },
					{ text: 'Source code (free plugin)', link: repo },
				],
			},
		],

		sidebar: {
			'/free/': [
				{
					text: 'Free plugin',
					items: [
						{ text: 'Overview', link: '/free/' },
						{ text: 'Getting started', link: '/free/getting-started' },
					],
				},
				{
					text: 'Guides',
					items: [
						{ text: 'Finding users', link: '/free/finding-users' },
						{ text: 'Preview and delete', link: '/free/preview-and-delete' },
						{ text: 'Large sites and background jobs', link: '/free/background-jobs' },
						{ text: 'Export to CSV', link: '/free/export' },
						{ text: 'Log and privacy (GDPR)', link: '/free/log-and-privacy' },
						{ text: 'Multisite', link: '/free/multisite' },
						{ text: 'WP-CLI', link: '/free/wp-cli' },
						{ text: 'Troubleshooting and FAQ', link: '/free/troubleshooting' },
					],
				},
				{
					text: 'Developers',
					items: [
						{ text: 'Hooks reference', link: '/free/developers/hooks' },
						{ text: 'Adding a filter type', link: '/free/developers/filter-types' },
						{ text: 'Data and uninstall', link: '/free/developers/data' },
					],
				},
			],
			'/pro/': [
				{
					text: 'Pro add-on',
					items: [
						{ text: 'Overview', link: '/pro/' },
						{ text: 'Installation and license', link: '/pro/installation' },
					],
				},
				{
					text: 'Features',
					items: [
						{ text: 'Recycle bin', link: '/pro/recycle-bin' },
						{ text: 'Saved filters', link: '/pro/saved-filters' },
						{ text: 'Scheduled cleanups', link: '/pro/scheduled-cleanups' },
						{ text: 'More filters', link: '/pro/filters' },
						{ text: 'Protection and blocked registrations', link: '/pro/protection' },
						{ text: 'WooCommerce customer data', link: '/pro/woocommerce' },
						{ text: 'Notifications', link: '/pro/notifications' },
						{ text: 'Export columns and last logins', link: '/pro/export-and-logins' },
						{ text: 'Multisite network', link: '/pro/multisite' },
						{ text: 'WP-CLI', link: '/pro/wp-cli' },
						{ text: 'License, trial and refunds', link: '/pro/license-faq' },
					],
				},
				{
					text: 'Developers',
					items: [
						{ text: 'Pro hooks reference', link: '/pro/developers/hooks' },
						{ text: 'Data and uninstall', link: '/pro/developers/data' },
					],
				},
			],
		},

		socialLinks: [ { icon: 'github', link: repo } ],
		search: { provider: 'local' },
		editLink: { pattern: `${ repo }/edit/master/docs/:path`, text: 'Edit this page on GitHub' },
		footer: {
			message: 'The free plugin is released under the GPL v2 or later. The Pro add-on is sold through Freemius.',
			copyright: '© Maksym Vasylchuk',
		},
	},
} );
