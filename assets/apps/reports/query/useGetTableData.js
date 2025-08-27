/* eslint-disable @tanstack/query/exhaustive-deps */
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export const useGetOverviewTableData = ( periods = {} ) => {
	const periodStart = periods?.start || '';
	const periodEnd = periods?.end || '';

	return useQuery( {
		queryKey: [ 'overviewTableData', periodStart, periodEnd ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/table_data', periods ),
			} );
		},
	} );
};

export const useGetDetailedTableData = ( periods = {} ) => {
	const periodStart = periods?.start || '';
	const periodEnd = periods?.end || '';

	return useQuery( {
		queryKey: [ 'detailedTableData', periodStart, periodEnd ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/users_download_data', periods ),
			} );
		},
	} );
};
