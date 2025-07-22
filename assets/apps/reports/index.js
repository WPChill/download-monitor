import { createRoot } from '@wordpress/element';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from './query/client';
import { StateProvider } from './context/state-context';
import ReportsWrapper from './ReportsWrapper';
import './index.css';

document.addEventListener( 'DOMContentLoaded', () => {
	const page = document.getElementById( 'dlm_reports_page' );

	if ( ! page ) {
		return;
	}

	const root = createRoot( page );
	root.render(
		<QueryClientProvider client={ queryClient }>
			<StateProvider >
				<ReportsWrapper />
			</StateProvider>
		</QueryClientProvider>,
	);
} );
