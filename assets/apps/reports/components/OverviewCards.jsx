import { __ } from '@wordpress/i18n';
import useStateContext from '../context/useStateContext';
import { useGetOverviewCards } from '../query/useGetCards';
import styles from './ReportsCards.module.scss';
import { Spinner } from '@wordpress/components';
import Slot from './Slot';
export default function OverviewCards() {
	const { state } = useStateContext();
	const {
		data: cards,
		isLoading,
		error,
	} = useGetOverviewCards( state.periods );

	return (
		<div className={ styles.dlmReportsCardsWrapper } >
			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Total Downloads', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.total || 0 }
				</p>
				<span id="dlm-card-slot-total" />
				<Slot name="dlm.card.total.after" containerId="dlm-card-slot-total" cards={ cards } />
			</div>

			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Today Downloads', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.today || 0 }
				</p>
				<span id="dlm-card-slot-today" />
				<Slot name="dlm.card.today.after" containerId="dlm-card-slot-today" cards={ cards } />
			</div>

			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Most popular download', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.most_popular?.title || __( 'No Title', 'download-monitor' ) }
				</p>
				<span id="dlm-card-slot-popular" />
				<Slot name="dlm.card.popular.after" containerId="dlm-card-slot-popular" cards={ cards } />
			</div>

			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Daily average downloads', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.average || 0 }
				</p>
				<span id="dlm-card-slot-average" />
				<Slot name="dlm.card.average.after" containerId="dlm-card-slot-average" cards={ cards } />
			</div>

			<span id="dlm-cards-slot-after" />
			<Slot name="dlm.cards.after" containerId="dlm-cards-slot-after" cards={ cards } />
		</div>
	);
}
