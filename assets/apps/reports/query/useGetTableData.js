import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export const useGetTableData = ( periods = {} ) => {
	return useQuery( {
		queryKey: [ 'tableData', periods ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/table_data', periods ),
			} );
		},
	} );
};
