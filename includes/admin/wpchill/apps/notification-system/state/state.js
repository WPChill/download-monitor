import { useReducer } from '@wordpress/element';
import reducer from './reducer';
import { initialState } from './default-state';
import { StateContext } from './use-wpchill-state';

export function StateProvider( { children, galleryId } ) {
	const [ state, dispatch ] = useReducer(
		reducer,
		initialState( galleryId ),
	);
	return (
		<StateContext.Provider value={ { state, dispatch } }>
			{ children }
		</StateContext.Provider>
	);
}
