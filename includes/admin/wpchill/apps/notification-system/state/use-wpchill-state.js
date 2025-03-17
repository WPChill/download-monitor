import { createContext, useContext } from '@wordpress/element';
export const StateContext = createContext();

export function useWpchillState() {
	const context = useContext( StateContext );
	if ( ! context ) {
		throw new Error( 'useWpchillState must be used within a StateProvider' );
	}
	return context;
}
