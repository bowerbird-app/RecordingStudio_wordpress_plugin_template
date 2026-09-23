import assert from 'node:assert/strict';
import {
	pageSelectOptions,
	pickerMode,
} from '../../src/recording-studio-widget/page-picker.mjs';

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
