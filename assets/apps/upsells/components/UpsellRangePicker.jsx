import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';
import styles from './RangePicker.module.scss';
import { format, subDays } from 'date-fns';

export default function UpsellRangePicker() {
	const yesterday = subDays( new Date(), 1 );
	const startDate = subDays( yesterday, 6 );
	const endDate = yesterday;
	const compareEnd = subDays( new Date(), 8 );
	const compareStart = subDays( compareEnd, 6 );

	const displayValue = `${ format( startDate, 'MMM dd, yyyy' ) } - ${ format( endDate, 'MMM dd, yyyy' ) }`;
	const compareValue = `${ format( compareStart, 'MMM dd, yyyy' ) } - ${ format( compareEnd, 'MMM dd, yyyy' ) }`;
	const link = 'https://www.download-monitor.com/pricing/?utm_source=reports_page&utm_medium=lite-vs-pro&utm_campaign=dlm-enhanced_metrics';

	return (
		<div className={ styles.wrapper }>
			<div
				className={ styles.input }
			>
				<div className={ styles.inputWrapper }>
					<span className={ styles.displayValue } >{ displayValue }</span>
					<span className={ styles.compareValue }>
						{ __( 'Compared to:', 'download-monitor' ) } { compareValue }
					</span>
				</div>
				<div className={ styles.iconWrapper }>
					<Dashicon icon="arrow-down-alt2" className={ styles.icon } />
				</div>
			</div>
			<a className={ styles.badge } href={ link } target="_BLANK" rel="noreferrer">
				{ __( 'Paid', 'download-monitor' ) }
			</a>
		</div>
	);
}
