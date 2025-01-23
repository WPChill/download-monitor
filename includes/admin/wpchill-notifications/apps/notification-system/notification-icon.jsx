import { useWpchillState } from './state/use-wpchill-state';
import { setShowContainer } from './state/actions';

export function NotificationIcon() {
	const { state, dispatch } = useWpchillState();
	const { showContainer, visibleNotifications } = state;
	const handleClick = () => {
		dispatch( setShowContainer( showContainer ? false : true ) );
	};

	return (
		<button className="notification-icon" onClick={ handleClick }>
			<span className="warn-icon">
				{ visibleNotifications.length }
			</span>
		</button>
	);
}
