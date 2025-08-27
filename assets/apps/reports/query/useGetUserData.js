/* eslint-disable @tanstack/query/exhaustive-deps */
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export const useGetUserData = ( periods = {} ) => {
	const periodStart = periods?.start || '';
	const periodEnd = periods?.end || '';
	return useQuery( {
		queryKey: [ 'detailedUserData', periodStart, periodEnd ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/users_data', periods ),
			} );
		},
	} );
};

