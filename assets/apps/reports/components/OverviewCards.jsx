import { __ } from '@wordpress/i18n';
import useStateContext from '../context/useStateContext';
import { useGetCards } from '../query/useGetCards';
import styles from './OverviewCards.module.scss';
import { Spinner } from '@wordpress/components';
import Slot from './Slot';
export default function OverviewCards() {
	const { state } = useStateContext();
	const {
		data: cards,
		isLoading,
		error,
	} = useGetCards( state.periods );
	//return `start: ${ state.periods.start } end: ${ state.periods.end }`;

	return (
		<div className={ styles.dlmReportsCardsWrapper } >
			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle } >{ __( 'Total Downloads', 'download-monitor' ) }</h3>
				{ isLoading
					? ( <p className={ styles.dlmReportsCardValue } ><Spinner /></p> )
					: ( <p className={ styles.dlmReportsCardValue } >{ cards?.total || 0 }</p> ) }
				<span id="dlm-card-slot-total" />
				<Slot name="dlm.card.total.after" containerId="dlm-card-slot-total" cards={ cards } />
			</div>
			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle } >{ __( 'Today Downloads', 'download-monitor' ) }</h3>
				{ isLoading
					? ( <p className={ styles.dlmReportsCardValue } ><Spinner /></p> )
					: ( <p className={ styles.dlmReportsCardValue } >{ cards?.today || 0 }</p> ) }
			</div>
			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle } >{ __( 'Most popular download', 'download-monitor' ) }</h3>
				{ isLoading
					? ( <p className={ styles.dlmReportsCardValue } ><Spinner /></p> )
					: ( <p className={ styles.dlmReportsCardValue } >{ cards?.most_popular?.title || __( 'No Title', 'download-monitor' ) }</p> ) }
			</div>
			<div className={ styles.dlmReportsCard }>
				<h3 className={ styles.dlmReportsCardTitle } >{ __( 'Daily average downloads', 'download-monitor' ) }</h3>
				{ isLoading
					? ( <p className={ styles.dlmReportsCardValue } ><Spinner /></p> )
					: ( <p className={ styles.dlmReportsCardValue } >{ cards?.average || 0 }</p> ) }
			</div>
		</div>
	);
}
