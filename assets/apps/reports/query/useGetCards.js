import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export const useGetOverviewCards = ( periods = {} ) => {
	return useQuery( {
		queryKey: [ 'overview_cards', periods ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/overview_card_data', periods ),
			} );
		},
		enabled: Object.keys( periods ).length > 0,
	} );
};

export const useGetDetailedCards = ( periods = {} ) => {
	return useQuery( {
		queryKey: [ 'detailed_cards', periods ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/detailed_card_data', periods ),
			} );
		},
		enabled: Object.keys( periods ).length > 0,
	} );
};
