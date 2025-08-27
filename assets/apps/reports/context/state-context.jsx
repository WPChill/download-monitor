import { createContext, useReducer } from '@wordpress/element';
import { reducer } from './reducer';
import { initialState } from './initial-state';

export const StateContext = createContext( initialState );

export const StateProvider = ( { children } ) => {
	const [ state, dispatch ] = useReducer(
		reducer,
		initialState(),
	);

	return (
		<StateContext.Provider value={ { state, dispatch } }>
			{ children }
		</StateContext.Provider>
	);
};
