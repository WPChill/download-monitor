import useStateContext from '../context/useStateContext';
import { useGetDetailedCards } from '../query/useGetCards';
import styles from './ReportsCards.module.scss';
import { Spinner } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import Card from './Card';

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
			<Card label={ __( 'Logged In Downloads', 'download-monitor' ) } value={ loggedIn } color="#8280FF" icon="admin-users" type="loggedIn" cards={ cards } />
			<Card label={ __( 'Guest Downloads', 'download-monitor' ) } value={ loggedOut } color="#4AD991" icon="visibility" type="guest" cards={ cards } />
			<Card label={ __( 'Most Active User', 'download-monitor' ) } value={ popular } color="#FF9066" icon="star-filled" type="mostActive" cards={ cards } />
			{ applyFilters( 'dlm.detailed.cards.after', '', { state, cards } ) }
		</div>
	);
}
