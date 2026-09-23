import { registerBlockType } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';

import Edit from './edit';
import metadata from './block.json';
import { productText } from './product-text.mjs';

function blockIcon() {
	const logoUrl = productText( 'logoUrl' );
	if ( logoUrl === '' ) {
		return null;
	}

	return createElement( 'img', {
		src: logoUrl,
		alt: '',
		width: 24,
		height: 24,
	} );
}

const settings = {
	edit: Edit,
};
const title = productText( 'name' );
const description = productText( 'description' );
if ( title !== '' ) {
	settings.title = title;
}
if ( description !== '' ) {
	settings.description = description;
}
const icon = blockIcon();
if ( icon ) {
	settings.icon = icon;
}

registerBlockType( metadata.name, settings );
