import { registerBlockType } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';

import Edit from './edit';
import metadata from './block.json';

function blockIcon() {
	const logoUrl = window.recordingStudioProductConfig?.logoUrl;
	if ( typeof logoUrl !== 'string' || logoUrl === '' ) {
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
const icon = blockIcon();
if ( icon ) {
	settings.icon = icon;
}

registerBlockType( metadata.name, settings );
