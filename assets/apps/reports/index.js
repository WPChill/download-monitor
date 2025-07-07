import { createRoot } from '@wordpress/element';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from './query/client';
import { StateProvider } from './context/state-context';
import ReportsWrapper from './ReportsWrapper';
import * as slotFillRegistry from './utils/slotFillRegistry';
import './index.css';

document.addEventListener( 'DOMContentLoaded', () => {
	// Global SlotFill Registry so it can be used from extensions.
	window.dlmSlotFillRegistry = slotFillRegistry;

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
