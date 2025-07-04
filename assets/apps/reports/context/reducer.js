export const actionTypes = {
	SET_PERIODS: 'SET_PERIODS',
};

export const reducer = ( state, action ) => {
	switch ( action.type ) {
		case actionTypes.SET_PERIODS:
			return {
				...state,
				periods: action.payload,
			};
		default:
			return state;
	}
};
