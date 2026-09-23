export function productText( key ) {
	const config =
		typeof window === 'undefined'
			? undefined
			: window.recordingStudioProductConfig;
	const value = config?.[ key ];
	return typeof value === 'string' ? value.trim() : '';
}
