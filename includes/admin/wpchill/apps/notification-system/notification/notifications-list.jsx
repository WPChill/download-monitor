import { useEffect } from '@wordpress/element';
import { Panel, PanelBody, PanelRow, __experimentalText as Text, Button } from '@wordpress/components';
import { Markup } from 'interweave';
import { __ } from '@wordpress/i18n';
import { useNotificationDismiss } from '../query/useNotificationDismiss';
import { useQueryClient } from '@tanstack/react-query';
import { NotificationActions } from './notification-actions';
import { useWpchillState } from '../state/use-wpchill-state';
import { setOpenPanels, setVisibleNotifications } from '../state/actions';

export function NotificationsList() {
	const mutation = useNotificationDismiss();
	const queryClient = useQueryClient();
	const { state, dispatch } = useWpchillState();
	const { visibleNotifications, openPanels } = state;

	const dismissNotification = ( id, permanent = false ) => {
		mutation.mutate( { id, permanent }, {
			onSettled: () => {
				queryClient.invalidateQueries( [ 'notifications' ] );
			},
		} );
		setVisibleNotifications( ( prevNotifications ) =>
			prevNotifications.filter( ( notification ) => notification.id !== id ),
		);
	};

	useEffect( () => {
		visibleNotifications.forEach( ( notification ) => {
			if ( notification.timed && openPanels.includes( notification.id ) ) {
				setTimeout( () => {
					dismissNotification( notification.id );
				}, notification.timed );
			}
		} );
	}, [ visibleNotifications, openPanels ] );

	const handleTogglePanel = ( id ) => {
		dispatch( setOpenPanels( [ ...openPanels, id ] ) );
	};

	return (
		<Panel>
			{ visibleNotifications?.length === 0 && (
				<div className="notification-log-empty">
					<Text>{ __( 'No notifications!', 'download-monitor' ) }</Text>
				</div>
			) }
			{ visibleNotifications?.length > 0 &&
				visibleNotifications?.map( ( notification ) => (
					<PanelBody
						title={
							<>
								<span className="notification-source-icon-wrap">
									<img className="notification-source-icon" src={ notification?.source?.icon || '' } alt={ notification?.source?.name || '' } />
								</span>
								{ notification?.source?.name && (
									<span className="notification-source-name">{ notification.source.name }</span>
								) }
								<span className="notification-title"><Markup content={ notification.title } /></span>
								{ ( notification?.time_ago !== undefined || notification.time_ago ) &&
								<span className="notification_time">
									{ notification.time_ago }
								</span> }
							</>
						}
						key={ notification?.id }
						initialOpen={ false }
						isOpen={ openPanels.includes( notification.id ) }
						onToggle={ () => handleTogglePanel( notification.id ) }
					>
						<PanelRow className="notification-row">
							<Text variant="muted">
								<Markup content={ notification.message } />
							</Text>
							{ ( notification?.dismissible === undefined || notification.dismissible ) &&
							<Button className="notification_dismiss_button" onClick={ () => dismissNotification( notification.id ) }>
								{ __( 'Dismiss', 'download-monitor' ) }
							</Button> }
						</PanelRow>
						{ ( notification?.actions !== undefined && notification.actions.length > 0 ) &&
						<PanelRow className="notification-row">
							<NotificationActions actions={ notification.actions } id={ notification.id } onDismiss={ dismissNotification } />
						</PanelRow> }
					</PanelBody>
				) ) }
		</Panel>
	);
}
