import useStateContext from '../context/useStateContext';
import { useGetOverviewCards } from '../query/useGetCards';
import styles from './ReportsCards.module.scss';
import { Spinner, Dashicon } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

export default function OverviewCards() {
	const { state } = useStateContext();
	const {
		data: cards,
		isLoading,
	} = useGetOverviewCards( state.periods );

	return (
		<div className={ styles.dlmReportsCardsWrapper } >
			<div className={ `${ styles.dlmReportsCard } ${ styles.total }` } >
				<div className={ styles.dlmReportsCardIconWrap }>
					<Dashicon icon="download" className={ styles.dlmReportsCardIcon } />
				</div>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Total', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.total || 0 }
				</p>
				{ applyFilters( 'dlm.overview.card.total.after', '123', { state, cards } ) }
			</div>

			<div className={ `${ styles.dlmReportsCard } ${ styles.today }` } >
				<div className={ styles.dlmReportsCardIconWrap }>
					<Dashicon icon="clock" className={ styles.dlmReportsCardIcon } />
				</div>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Today', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.today || 0 }
				</p>
				{ applyFilters( 'dlm.overview.card.today.after', '', { state, cards } ) }
			</div>

			<div className={ `${ styles.dlmReportsCard } ${ styles.popular }` } >
				<div className={ styles.dlmReportsCardIconWrap }>
					<Dashicon icon="star-filled" className={ styles.dlmReportsCardIcon } />
				</div>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Most popular', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.most_popular?.title || __( 'No Title', 'download-monitor' ) }
				</p>
				{ applyFilters( 'dlm.overview.card.popular.after', '', { state, cards } ) }
			</div>

			<div className={ `${ styles.dlmReportsCard } ${ styles.average }` } >
				<div className={ styles.dlmReportsCardIconWrap }>
					<Dashicon icon="chart-bar" className={ styles.dlmReportsCardIcon } />
				</div>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ __( 'Daily average', 'download-monitor' ) }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ isLoading ? <Spinner /> : cards?.average || 0 }
				</p>
				{ applyFilters( 'dlm.overview.card.average.after', '', { state, cards } ) }
			</div>

			{ applyFilters( 'dlm.overview.cards.after', '', { state, cards } ) }
		</div>
	);
}
