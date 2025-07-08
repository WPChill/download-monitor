import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export const useGetChartData = ( periods = {} ) => {
	return useQuery( {
		queryKey: [ 'chartData', periods ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/graph_data', periods ),
			} );
		},
	} );
};
