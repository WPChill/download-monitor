import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export const useGetUserData = ( periods = {} ) => {
	return useQuery( {
		queryKey: [ 'overviewTableData', periods ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/users_data', periods ),
			} );
		},
	} );
};

