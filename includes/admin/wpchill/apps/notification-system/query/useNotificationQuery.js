import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
export const useNotificationQuery = () => {
	return useQuery( {
		queryKey: [ 'notifications' ],
		queryFn: async () => {
			const data = await apiFetch( {
				path: `/wpchill/v1/notifications`,
				method: 'GET',
			} );
			return data;
		},
		refetchInterval: ( query ) => query?.state?.fetchFailureCount < 5 ? 5000 : false,
	} );
};
