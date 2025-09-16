import useStateContext from '../context/useStateContext';
import { useGetDetailedCards } from '../query/useGetCards';
import styles from './ReportsCards.module.scss';
import { Spinner } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import Summary from './Summary';

export default function DetailedCards() {
	const { state } = useStateContext();
	const {
		data: cards,
		isLoading,
	} = useGetDetailedCards( state.periods );

	const loggedIn = isLoading ? <Spinner /> : ( cards?.logged_in ?? 0 ).toLocaleString();
	const loggedOut = isLoading ? <Spinner /> : ( cards?.logged_out ?? 0 ).toLocaleString();
	const popular = isLoading ? <Spinner /> : cards?.most_active?.name || __( 'No Title', 'download-monitor' );

	return (
		<div className={ styles.dlmReportsCardsWrapper }>
			<Summary label={ __( 'Guest Downloads', 'download-monitor' ) } value={ loggedOut } type="guest" cards={ cards } />
			<Summary label={ __( 'Logged In Downloads', 'download-monitor' ) } value={ loggedIn } type="loggedIn" cards={ cards } />
			<Summary label={ __( 'Most Active User', 'download-monitor' ) } value={ popular } type="mostActive" cards={ cards } />
			{ applyFilters( 'dlm.detailed.cards.after', '', { state, cards } ) }
		</div>
	);
}
