import { NotificationIcon } from './notification-icon';
import { NotificationsContainer } from './notifications-container';
import { useWpchillState } from './state/use-wpchill-state';
import { useNotificationQuery } from './query/useNotificationQuery';
import { useEffect } from '@wordpress/element';
import { setVisibleNotifications } from './state/actions';

export function Notifications() {
	const { data, isLoading } = useNotificationQuery();
	const { state, dispatch } = useWpchillState();
	const { closedBubble, showContainer } = state;

	useEffect( () => {
		if ( ! isLoading && data ) {
			const allNotifications = Object.values( data ).flat();
			dispatch( setVisibleNotifications( allNotifications ) );
		}
	}, [ data, isLoading, dispatch ] );

	if ( 0 === state.visibleNotifications.length || closedBubble ) {
		return null;
	}

	return (
		<>
			<NotificationIcon />
			{ showContainer && <NotificationsContainer /> }
		</>
	);
}
