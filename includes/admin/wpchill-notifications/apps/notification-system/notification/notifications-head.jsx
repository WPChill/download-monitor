import { __ } from '@wordpress/i18n';
import { useWpchillState } from '../state/use-wpchill-state';
import { setShowContainer } from '../state/actions';
import { Button } from '@wordpress/components';

export function NotificationsHead() {
	const { dispatch } = useWpchillState();

	const closePanel = () => {
		dispatch( setShowContainer(false) );
	};

	return <>
		<h2>{ __( 'WPChill Notification Center', 'modula-best-grid-gallery' ) }</h2>
		<Button onClick={ closePanel }>
			<span className="dashicons dashicons-no-alt" ></span>
		</Button>
	</>;
}
