export function pageOptionLabel( page, untitledLabel ) {
	const title = typeof page?.title === 'string' ? page.title.trim() : '';
	return title === '' ? untitledLabel : title;
}

export function pageSelectOptions( pages, pickLabel, untitledLabel ) {
	const options = [ { label: pickLabel, value: '' } ];
	for ( const page of pages ) {
		const id = typeof page?.id === 'string' ? page.id : '';
		if ( id === '' ) {
			continue;
		}
		options.push( {
			label: pageOptionLabel( page, untitledLabel ),
			value: id,
		} );
	}
	return options;
}

export function pickerMode( status, pages ) {
	if ( status !== 'ready' ) {
		return 'loading';
	}
	return pages.length === 0 ? 'empty' : 'choose';
}
