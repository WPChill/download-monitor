export default function reducer( state, action ) {
	switch ( action.type ) {
		case 'SET_CLOSE_BUBBLE':
			return { ...state, closedBubble: action.payload };
		case 'SET_SHOW_CONTAINER':
			return { ...state, showContainer: action.payload };
		case 'SET_OPEN_PANELS':
			return { ...state, openPanels: action.payload };
		case 'SET_VISIBLE_NOTIFICATIONS':
			return { ...state, visibleNotifications: action.payload };
		default:
			return state;
	}
}
