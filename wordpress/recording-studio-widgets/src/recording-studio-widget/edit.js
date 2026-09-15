/**
 * Block editor: page recording id control + REST preview.
 *
 * @package recording-studio-widget
 */

import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Spinner, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

/**
 * @param {string} pageRecordingId UUID.
 * @returns {Promise<object>} BrowserPayload v1 JSON.
 */
async function fetchEditorPreview( pageRecordingId ) {
	return apiFetch( {
		path: `/recording-studio/v1/preview/${ pageRecordingId }`,
	} );
}

/**
 * @param {{ attributes: { pageRecordingId: string }, setAttributes: (p: object) => void }} props Block props.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { pageRecordingId = '' } = attributes;
	const [ preview, setPreview ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ loading, setLoading ] = useState( false );

	useEffect( () => {
		const trimmed = pageRecordingId.trim();
		if ( ! trimmed ) {
			setPreview( null );
			setError( null );
			return;
		}

		let cancelled = false;
		const timer = setTimeout( () => {
			setLoading( true );
			setError( null );
			fetchEditorPreview( trimmed )
				.then( ( payload ) => {
					if ( ! cancelled ) {
						setPreview( payload );
						setLoading( false );
					}
				} )
				.catch( ( err ) => {
					if ( ! cancelled ) {
						setPreview( null );
						setError(
							err?.message ||
								__(
									'Could not load a preview from the host.',
									'recording-studio-widget'
								)
						);
						setLoading( false );
					}
				} );
		}, 400 );

		return () => {
			cancelled = true;
			clearTimeout( timer );
		};
	}, [ pageRecordingId ] );

	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'WordPress Plugin Demo',
						'recording-studio-widget'
					) }
				>
					<TextControl
						label={ __(
							'Page recording id',
							'recording-studio-widget'
						) }
						value={ pageRecordingId }
						onChange={ ( value ) =>
							setAttributes( { pageRecordingId: value } )
						}
						help={ __(
							'UUID of the Page recording to embed from the host.',
							'recording-studio-widget'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ ! pageRecordingId.trim() && (
					<p>
						{ __(
							'Add a page recording id in the block settings to preview the WordPress Plugin Demo embed.',
							'recording-studio-widget'
						) }
					</p>
				) }
				{ loading && <Spinner /> }
				{ error && (
					<Notice status="warning" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ preview?.html && (
					<div
						className="rs-wordpress-plugin-demo-editor-preview"
						// eslint-disable-next-line react/no-danger
						dangerouslySetInnerHTML={ { __html: preview.html } }
					/>
				) }
			</div>
		</>
	);
}
