export const actionTypes = {
	SET_PERIODS: 'SET_PERIODS',
	SET_ACTIVE_TAB: 'SET_ACTIVE_TAB',
	SET_DETAILED_DOWNLOADS: 'SET_DETAILED_DOWNLOADS',
	SET_OVERVIEW_DOWNLOADS: 'SET_OVERVIEW_DOWNLOADS',
};

export const reducer = ( state, action ) => {
	switch ( action.type ) {
		case actionTypes.SET_PERIODS:
			return {
				...state,
				periods: action.payload,
			};
		case actionTypes.SET_ACTIVE_TAB:
			return {
				...state,
				activeTab: action.payload,
			};
		case actionTypes.SET_DETAILED_DOWNLOADS:
			return {
				...state,
				detailedDownloads: action.payload,
			};
		case actionTypes.SET_OVERVIEW_DOWNLOADS:
			return {
				...state,
				overviewDownloads: action.payload,
			};
		default:
			return {
				...state,
				[ action.slug ]: action.payload,
			};
	}
};
