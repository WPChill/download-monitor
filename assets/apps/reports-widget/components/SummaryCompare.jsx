import { Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import styles from './SummaryCompare.module.scss';

function renderDiff( current, compare ) {
	if ( typeof current !== 'number' || typeof compare !== 'number' ) {
		return null;
	}

	if ( current === compare ) {
		return null;
	}

	let diffPercent;
	if ( 0 === compare ) {
		diffPercent = 100;
	} else {
		diffPercent = ( ( current - compare ) / compare ) * 100;
	}

	const roundedPercent = Math.abs( diffPercent.toFixed( 1 ) );
	const spanClass = diffPercent > 0 ? `${ styles.diff } ${ styles.up }` : `${ styles.diff } ${ styles.down }`;

	const previousPeriodText = __( 'Previous period: ', 'dlm-enhanced-metrics' ) + compare.toLocaleString();

	return (
		<Tooltip text={ previousPeriodText } position="top">
			<span className={ spanClass }>
				{ roundedPercent }%
			</span>
		</Tooltip>
	);
}

export default function SummaryCompare( { cards, type } ) {
	let value = null;
	let diff = null;

	switch ( type ) {
		case 'total':
			value = cards?.compare_total;
			diff = renderDiff( cards?.total, cards?.compare_total );
			break;

		case 'average':
			value = cards?.compare_average;
			diff = renderDiff( cards?.average, cards?.compare_average );
			break;

		default:
			return null;
	}

	if ( value === undefined || value === null ) {
		return null;
	}

	return diff;
}
