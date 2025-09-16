/* eslint-disable camelcase */
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export const useGetWidgetCards = () => {
	const formatDate = ( date ) => date.toISOString().split( 'T' )[ 0 ];
	const endDate = new Date();

	const startDate = new Date( endDate );
	startDate.setDate( endDate.getDate() - 29 );

	const start = formatDate( startDate );
	const end = formatDate( endDate );

	return useQuery( {
		queryKey: [ 'dlm_widget_cards', start, end ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/overview_card_data', {
					start,
					end,
				} ),
			} );
		},
	} );
};
