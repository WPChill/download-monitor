import { NotificationsList } from './notification/notifications-list';
import { NotificationsHead } from './notification/notifications-head';
import { NotificationsFooter } from './notification/notifications-footer';

export function NotificationsContainer() {
	return (
		<div className="notification-container">
			<div className="notification-header">
				<NotificationsHead />
			</div>
			<NotificationsList />
			<div className="notification-footer">
				<NotificationsFooter />
			</div>
		</div>
	);
}
