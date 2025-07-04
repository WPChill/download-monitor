const registry = {};

export function registerFill( name, component ) {
	if ( ! registry[ name ] ) {
		registry[ name ] = [];
	}
	registry[ name ].push( component );
}

export function unregisterFill( name, component ) {
	if ( ! registry[ name ] ) {
		return;
	}
	registry[ name ] = registry[ name ].filter( ( c ) => c !== component );
}

export function getFills( name ) {
	return registry[ name ] || [];
}
