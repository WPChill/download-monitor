import { useGetWidgetCards } from '../query/useGetWidgetCards';
import styles from './WidgetCards.module.scss';
import { Spinner } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import Summary from './Summary';

export default function WidgetCards() {
	const {
		data: cards,
		isLoading,
	} = useGetWidgetCards();

	const total = isLoading ? <Spinner /> : ( cards?.total ?? 0 ).toLocaleString();
	const today = isLoading ? <Spinner /> : ( cards?.today ?? 0 ).toLocaleString();
	const popular = isLoading ? <Spinner /> : cards?.most_popular?.title || __( 'No Title', 'download-monitor' );
	const average = isLoading ? <Spinner /> : ( cards?.average ?? 0 ).toLocaleString();

	return (
		<div className={ styles.dlmReportsCardsWrapper }>
			<Summary label={ __( 'Today Downloads', 'download-monitor' ) } value={ today } type="today" cards={ cards } />
			<Summary label={ __( 'Total Downloads', 'download-monitor' ) } value={ total } type="total" cards={ cards } />
			<Summary label={ __( 'Daily Average', 'download-monitor' ) } value={ average } type="average" cards={ cards } />
			<Summary label={ __( 'Most Popular', 'download-monitor' ) } value={ popular } type="popular" cards={ cards } />
			{ applyFilters( 'dlm.widget.cards.after', '', { cards } ) }
		</div>
	);
}
