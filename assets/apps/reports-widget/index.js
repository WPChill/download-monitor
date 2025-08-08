import { createRoot } from '@wordpress/element';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from './query/client';
import ReportsWrapper from './ReportsWrapper';
import './index.css';

document.addEventListener( 'DOMContentLoaded', () => {
	const widget = document.getElementById( 'dlm_reports_widget' );

	if ( ! widget ) {
		return;
	}

	const root = createRoot( widget );
	root.render(
		<QueryClientProvider client={ queryClient }>
			<ReportsWrapper />
		</QueryClientProvider>,
	);
} );
