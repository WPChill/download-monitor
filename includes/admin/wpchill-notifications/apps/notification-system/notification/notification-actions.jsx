import { Button } from '@wordpress/components';
import he from 'he';

export function NotificationActions( { actions, id, onDismiss } ) {
	const handleClick = ( action ) => {
		if ( action.callback && typeof window[ action.callback ] === 'function' ) {
			window[ action.callback ]( action, id );
		}

		if ( action.dismiss ) {
			onDismiss( id, action.permanent );
		}
	};

	return (
		<div className="notification-actions-wrapp">
			{ actions.map( ( action, index ) => (
				<Button
					key={ index }
					className={ action.class || 'notification-action' }
					{ ...( action.url ? { href: he.decode( action.url ) } : {} ) }
					{ ...( action.id ? { id: action.id } : {} ) }
					target={ action.target || '' }
					text={ he.decode( action.label || '' ) }
					variant={ action.variant || 'secondary' }
					size={ action.size || 'small' }
					onClick={ () => handleClick( action ) }
				/>
			) ) }
		</div>
	);
}
