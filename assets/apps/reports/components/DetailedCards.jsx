import useStateContext from '../context/useStateContext';
import { useGetDetailedCards } from '../query/useGetCards';
import styles from './ReportsCards.module.scss';
import { Spinner, Dashicon } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

export default function DetailedCards() {
	const { state } = useStateContext();
	const {
		data: cards,
		isLoading,
	} = useGetDetailedCards( state.periods );

	return (
		<div className={ styles.dlmReportsCardsWrapper } >
			<div className={ `${ styles.dlmReportsCard } ${ styles.loggedIn }` } >
				<div className={ styles.dlmReportsCardIconWrap }>
					<Dashicon icon="admin-users" className={ styles.dlmReportsCardIcon } />
				</div>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Logged in', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.logged_in || 0 }
				</p>
				{ applyFilters( 'dlm.detailed.card.loggedin.after', '', { state, cards } ) }
			</div>

			<div className={ `${ styles.dlmReportsCard } ${ styles.guest }` } >
				<div className={ styles.dlmReportsCardIconWrap }>
					<Dashicon icon="visibility" className={ styles.dlmReportsCardIcon } />
				</div>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Guest Downloads', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.logged_out || 0 }
				</p>
				{ applyFilters( 'dlm.detailed.card.guest.after', '', { state, cards } ) }
			</div>

			<div className={ `${ styles.dlmReportsCard } ${ styles.mostActive }` } >
				<div className={ styles.dlmReportsCardIconWrap }>
					<Dashicon icon="star-filled" className={ styles.dlmReportsCardIcon } />
				</div>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Most active user', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.most_active?.name || __( 'No Title', 'download-monitor' ) }
				</p>
				{ applyFilters( 'dlm.detailed.card.mostactive.after', '', { state, cards } ) }
			</div>

			{ applyFilters( 'dlm.detailed.cards.after', '', { state, cards } ) }
		</div>
	);
}
