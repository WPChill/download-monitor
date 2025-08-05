/* eslint-disable @tanstack/query/exhaustive-deps */
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export const useGetOverviewCards = ( periods = {} ) => {
	const periodStart = periods?.start || '';
	const periodEnd = periods?.end || '';
	return useQuery( {
		queryKey: [ 'overview_cards', periodStart, periodEnd ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/overview_card_data', periods ),
			} );
		},
		enabled: Object.keys( periods ).length > 0,
	} );
};

export const useGetDetailedCards = ( periods = {} ) => {
	const periodStart = periods?.start || '';
	const periodEnd = periods?.end || '';
	return useQuery( {
		queryKey: [ 'detailed_cards', periodStart, periodEnd ],
		queryFn: () => {
			return apiFetch( {
				path: addQueryArgs( 'download-monitor/v1/reports/detailed_card_data', periods ),
			} );
		},
		enabled: Object.keys( periods ).length > 0,
	} );
};
