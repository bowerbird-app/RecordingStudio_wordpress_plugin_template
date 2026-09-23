import assert from 'node:assert/strict';
import {
	pageSelectOptions,
	pickerMode,
} from '../../src/recording-studio-widget/page-picker.mjs';
import { productText } from '../../src/recording-studio-widget/product-text.mjs';

const options = pageSelectOptions(
	[
		{ id: 'page-1', title: 'Getting Started' },
		{ id: 'page-2', title: '   ' },
		{ id: '', title: 'Skip me' },
	],
	'Pick a page',
	'Untitled'
);

assert.deepEqual( options, [
	{ label: 'Pick a page', value: '' },
	{ label: 'Getting Started', value: 'page-1' },
	{ label: 'Untitled', value: 'page-2' },
] );

assert.equal( pickerMode( 'loading', [] ), 'loading' );
assert.equal( pickerMode( 'ready', [] ), 'empty' );
assert.equal(
	pickerMode( 'ready', [ { id: 'page-1', title: 'Getting Started' } ] ),
	'choose'
);

const hostRecord = {
	id: 'f25f49b5-e0e5-4adb-b96e-629e2857a4cd',
	type: 'Page',
	parent_id: 'b48f6bcf-3f8d-47a0-84e8-b40c438d37de',
	root_id: 'd1971764-b179-4736-9eab-207990cbb1da',
	created_at: '2026-09-23T05:28:31Z',
	updated_at: '2026-09-23T05:28:31Z',
	title: 'Getting Started',
};
assert.deepEqual(
	pageSelectOptions( [ hostRecord ], 'Pick a page', 'Untitled' ),
	[
		{ label: 'Pick a page', value: '' },
		{
			label: 'Getting Started',
			value: 'f25f49b5-e0e5-4adb-b96e-629e2857a4cd',
		},
	]
);

globalThis.window = {
	recordingStudioProductConfig: {
		name: 'WP Template Demo',
		description: 'Shows a page from your studio.',
		logoUrl: 'http://localhost:8888/logo.jpg',
	},
};
assert.equal( productText( 'name' ), 'WP Template Demo' );
assert.equal( productText( 'description' ), 'Shows a page from your studio.' );
assert.equal( productText( 'logoUrl' ), 'http://localhost:8888/logo.jpg' );
assert.equal( productText( 'missing' ), '' );
