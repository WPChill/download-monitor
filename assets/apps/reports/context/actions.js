import { actionTypes } from './reducer';

export const setPeriods = ( value ) => ( {
	type: actionTypes.SET_PERIODS,
	payload: value,
} );

export const setActiveTab = ( value ) => ( {
	type: actionTypes.SET_ACTIVE_TAB,
	payload: value,
} );

export const setChartOptions = ( value ) => ( {
	type: actionTypes.SET_CHART_OPTIONS,
	payload: value,
} );

