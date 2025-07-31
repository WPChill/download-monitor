import dayjs from 'dayjs';
import { applyFilters } from '@wordpress/hooks';

const getDefaultPeriods = () => {
	const periods = {
		start: dayjs().subtract( 7, 'day' ).format( 'YYYY-MM-DD' ),
		end: dayjs().format( 'YYYY-MM-DD' ),
	};

	return applyFilters( 'myplugin.initial_periods', periods );
};

export const initialState = () => ( {
	periods: getDefaultPeriods(),
	activeTab: 'overview',
	chart: {
		showCurrent: true,
		showCompare: true,
		compareOpacity: 1,
		currentOpacity: 1,
		groupBy: 'days',
	},
} );
