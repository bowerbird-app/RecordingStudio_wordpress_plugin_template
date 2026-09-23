import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';
import { pageSelectOptions, pickerMode } from './page-picker.mjs';
import { productText } from './product-text.mjs';

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

export default function Edit( { attributes, setAttributes } ) {
	const { pageRecordingId = '' } = attributes;
	const [ preview, setPreview ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ loading, setLoading ] = useState( false );
	const [ pages, setPages ] = useState( [] );
	const [ pagesStatus, setPagesStatus ] = useState( 'loading' );

	useEffect( () => {
		let cancelled = false;
		fetchPages()
			.then( ( payload ) => {
				if ( ! cancelled ) {
					setPages(
						Array.isArray( payload?.pages ) ? payload.pages : []
					);
					setPagesStatus( 'ready' );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setPages( [] );
					setPagesStatus( 'ready' );
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
	const mode = pickerMode( pagesStatus, pages );
	const pageOptions = pageSelectOptions(
		pages,
		__( 'Pick a page', 'recording-studio-widget' ),
		__( 'Untitled', 'recording-studio-widget' )
	);
	const productName =
		productText( 'name' ) ||
		__( 'WP Template Demo', 'recording-studio-widget' );
	const logoUrl = productText( 'logoUrl' );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ productName }>
					{ mode === 'loading' && <Spinner /> }
					{ mode === 'empty' && (
						<p>
							{ __(
								'No pages yet. Add one, then pick it here.',
								'recording-studio-widget'
							) }
						</p>
					) }
					{ mode === 'choose' && (
						<SelectControl
							label={ __( 'Page', 'recording-studio-widget' ) }
							value={ pageRecordingId }
							options={ pageOptions }
							onChange={ ( value ) =>
								setAttributes( { pageRecordingId: value } )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ ! pageRecordingId.trim() && (
					<div className="rs-embed-empty">
						{ logoUrl !== '' && (
							<img
								className="rs-embed-empty__logo"
								src={ logoUrl }
								alt=""
								width="40"
								height="40"
							/>
						) }
						<p className="rs-embed-empty__title">{ productName }</p>
						<p className="rs-embed-empty__body">
							{ __(
								'Pick a page on the block settings to embed.',
								'recording-studio-widget'
							) }
						</p>
					</div>
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
