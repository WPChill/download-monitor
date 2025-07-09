import { __ } from '@wordpress/i18n';
import useStateContext from '../context/useStateContext';
import { useGetDetailedCards } from '../query/useGetCards';
import styles from './ReportsCards.module.scss';
import { Spinner } from '@wordpress/components';
import Slot from './Slot';
export default function DetailedCards() {
	const { state } = useStateContext();
	const {
		data: cards,
		isLoading,
		error,
	} = useGetDetailedCards( state.periods );

	return (
		<div className={ styles.dlmReportsCardsWrapper } >
			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Logged in downloads', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.logged_in || 0 }
				</p>
				<span id="dlm-card-slot-logged-in" />
				<Slot name="dlm.card.loggedin.after" containerId="dlm-card-slot-logged-in" cards={ cards } />
			</div>

			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Guest Downloads', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.logged_out || 0 }
				</p>
				<span id="dlm-card-slot-guest" />
				<Slot name="dlm.card.guest.after" containerId="dlm-card-slot-guest" cards={ cards } />
			</div>

			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Most active user', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.most_active?.name || __( 'No Title', 'download-monitor' ) }
				</p>
				<span id="dlm-card-slot-most-active" />
				<Slot name="dlm.card.mostactive.after" containerId="dlm-card-slot-most-active" cards={ cards } />
			</div>

			<span id="dlm-cards-slot-after" />
			<Slot name="dlm.cards.detailed.after" containerId="dlm-cards-slot-after" cards={ cards } />
		</div>
	);
}
