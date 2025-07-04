import { createRoot } from '@wordpress/element';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from './query/client';
import { StateProvider } from './context/state-context';
import ReporstWrapper from './ReporstWrapper';
import './index.css';

document.addEventListener( 'DOMContentLoaded', () => {
	const settings = document.getElementById( 'dlm_reports_page' );

	if ( ! settings ) {
		return;
	}

	const root = createRoot( settings );
	root.render(
		<QueryClientProvider client={ queryClient }>
			<StateProvider >
				<ReporstWrapper />
			</StateProvider>
		</QueryClientProvider>,
	);
} );
