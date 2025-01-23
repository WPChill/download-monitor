import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient( {
	defaultOptions: {
		queries: {
			retry: ( failureCount ) => failureCount < 5,
		},
	},
} );
