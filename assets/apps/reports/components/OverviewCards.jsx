import useStateContext from '../context/useStateContext';
import { useGetOverviewCards } from '../query/useGetCards';
import styles from './ReportsCards.module.scss';
import { Spinner } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import Summary from './Summary';

export default function OverviewCards() {
	const { state } = useStateContext();
	const {
		data: cards,
		isLoading,
	} = useGetOverviewCards( state.periods );

	const total = isLoading ? <Spinner /> : ( cards?.total ?? 0 ).toLocaleString();
	const today = isLoading ? <Spinner /> : ( cards?.today ?? 0 ).toLocaleString();
	const popular = isLoading ? <Spinner /> : cards?.most_popular?.title || __( 'No Title', 'download-monitor' );
	const average = isLoading ? <Spinner /> : ( cards?.average ?? 0 ).toLocaleString();

	return (
		<div className={ styles.dlmReportsCardsWrapper }>
			<Summary label={ __( 'Total Downloads', 'download-monitor' ) } value={ total } type="total" cards={ cards } />
			{ ( null === state.periods.compare_start || 'undefined' === typeof state.periods.compare_start ) &&
				<Summary label={ __( 'Today Downloads', 'download-monitor' ) } value={ today } type="today" cards={ cards } />
			}
			<Summary label={ __( 'Most Popular Download', 'download-monitor' ) } value={ popular } type="popular" cards={ cards } />
			<Summary label={ __( 'Daily Average Downloads', 'download-monitor' ) } value={ average } type="average" cards={ cards } />
			{ applyFilters( 'dlm.overview.cards.after', '', { state, cards } ) }
		</div>
	);
}
