import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export const useGetOverviewTableData = ( periods = {} ) => {
	return useQuery( {
		queryKey: [ 'overviewTableData', periods ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/table_data', periods ),
			} );
		},
	} );
};

export const useGetDetailedTableData = ( periods = {} ) => {
	return useQuery( {
		queryKey: [ 'detailedTableData', periods ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/users_download_data', periods ),
			} );
		},
	} );
};
