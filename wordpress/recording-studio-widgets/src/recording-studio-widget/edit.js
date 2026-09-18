import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

/**
 * @param {string} pageRecordingId UUID.
 * @return {Promise<object>} BrowserPayload v1 JSON.
 */
async function fetchEditorPreview( pageRecordingId ) {
	return apiFetch( {
		path: `/recording-studio/v1/preview/${ pageRecordingId }`,
	} );
}

/**
 * @return {Promise<{ pages?: { id: string, title: string }[] }>} Page list.
 */
async function fetchPages() {
	return apiFetch( {
		path: '/recording-studio/v1/pages',
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
	const [ pages, setPages ] = useState( [] );

	useEffect( () => {
		let cancelled = false;
		fetchPages()
			.then( ( payload ) => {
				if ( ! cancelled ) {
					setPages(
						Array.isArray( payload?.pages ) ? payload.pages : []
					);
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setPages( [] );
				}
			} );
		return () => {
			cancelled = true;
		};
	}, [] );

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
	const pageOptions = [
		{
			label: __( 'Pick a page', 'recording-studio-widget' ),
			value: '',
		},
		...pages.map( ( page ) => ( {
			label: page.title || page.id,
			value: page.id,
		} ) ),
	];

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'WordPress Plugin Demo',
						'recording-studio-widget'
					) }
				>
					{ pages.length > 0 && (
						<SelectControl
							label={ __( 'Page', 'recording-studio-widget' ) }
							value={ pageRecordingId }
							options={ pageOptions }
							onChange={ ( value ) =>
								setAttributes( { pageRecordingId: value } )
							}
						/>
					) }
					<TextControl
						label={ __( 'Page id', 'recording-studio-widget' ) }
						value={ pageRecordingId }
						onChange={ ( value ) =>
							setAttributes( { pageRecordingId: value } )
						}
						help={ __(
							'Pick a page, or paste an id if the list is empty.',
							'recording-studio-widget'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ ! pageRecordingId.trim() && (
					<p>
						{ __(
							'Pick a page in the block settings to preview the WordPress Plugin Demo embed.',
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
						dangerouslySetInnerHTML={ { __html: preview.html } }
					/>
				) }
			</div>
		</>
	);
}
