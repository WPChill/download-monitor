import { actionTypes } from './reducer';

export const setPeriods = ( value ) => ( {
	type: actionTypes.SET_PERIODS,
	payload: value,
} );
