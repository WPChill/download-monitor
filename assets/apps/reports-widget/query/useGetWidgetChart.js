import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export const useGetWidgetChart = () => {
	const formatDate = ( date ) => date.toISOString().split( 'T' )[ 0 ];
	const endDate = new Date();

	const startDate = new Date( endDate );
	startDate.setDate( endDate.getDate() - 29 );

	const start = formatDate( startDate );
	const end = formatDate( endDate );
	return useQuery( {
		queryKey: [ 'chartData', start, end ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/graph_data', { start, end } ),
			} );
		},
	} );
};
