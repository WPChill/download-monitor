import { actionTypes } from './reducer';

export const setPeriods = ( value ) => ( {
	type: actionTypes.SET_PERIODS,
	payload: value,
} );

export const setActiveTab = ( value ) => ( {
	type: actionTypes.SET_ACTIVE_TAB,
	payload: value,
} );

export const setOverviewDownloads = ( value ) => ( {
	type: actionTypes.SET_OVERVIEW_DOWNLOADS,
	payload: value,
} );

export const setDetailedDownloads = ( value ) => ( {
	type: actionTypes.SET_DETAILED_DOWNLOADS,
	payload: value,
} );
