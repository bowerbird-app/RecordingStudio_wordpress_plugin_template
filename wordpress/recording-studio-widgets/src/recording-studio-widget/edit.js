import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<p { ...useBlockProps() }>
			{ __(
				'RecordingStudio Widget placeholder. This block does not load a live widget yet.',
				'recording-studio-widget'
			) }
		</p>
	);
}
