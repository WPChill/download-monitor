import './index.scss';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from './query/client';
import { Notifications } from './notifications';
import { StateProvider } from './state/state';
import { createRoot } from '@wordpress/element';

document.addEventListener( 'DOMContentLoaded', () => {
	const postsContainer = document.getElementById( 'wpwrap' );
	if ( postsContainer ) {
		const div = document.createElement( 'div' );
		const wrapper = document.createElement( 'div' );
		wrapper.setAttribute( 'id', 'wpchill-notifications-wrapper' );
		wrapper.classList.add( 'wpchill-best-grid-gallery' );
		div.setAttribute( 'id', 'wpchill-notifications-root' );

		postsContainer.prepend( wrapper );
		wrapper.appendChild( div );
		const root = createRoot(
			document.getElementById( 'wpchill-notifications-root' ),
		);
		root.render(
			<QueryClientProvider client={ queryClient }>
				<StateProvider>
					<Notifications />
				</StateProvider>
			</QueryClientProvider>,
		);
	}
} );
