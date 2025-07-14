import { __ } from '@wordpress/i18n';
import styles from './LogsFilter.module.scss';

export default function LogsFilter() {
	const link = 'https://www.download-monitor.com/pricing/?utm_source=reports_page&utm_medium=lite-vs-pro&utm_campaign=dlm-enhanced_metrics';

	return (
		<div className={ styles.wrapper }>
			<label className={ styles.label } htmlFor="custom-date-range-input">
				{ __( 'Filter logs by', 'download-monitor' ) }
			</label>
			<div className={ styles.item }>
				<input type="text" placeholder={ __( 'Filter by status', 'download-monitor' ) } disabled className={ styles.input } />
				<a className={ styles.badge } href={ link } target="_BLANK" rel="noreferrer">
					{ __( 'Paid', 'download-monitor' ) }
				</a>
			</div>
			<div className={ styles.item }>
				<input type="text" placeholder={ __( 'Filter by user', 'download-monitor' ) } disabled className={ styles.input } />
				<a className={ styles.badge } href={ link } target="_BLANK" rel="noreferrer">
					{ __( 'Paid', 'download-monitor' ) }
				</a>
			</div>
			<div className={ styles.item }>
				<input type="text" placeholder={ __( 'Filter by download category', 'download-monitor' ) } disabled className={ styles.input } />
				<a className={ styles.badge } href={ link } target="_BLANK" rel="noreferrer">
					{ __( 'Paid', 'download-monitor' ) }
				</a>
			</div>
			<div className={ styles.item }>
				<input type="text" placeholder={ __( 'Filter by download', 'download-monitor' ) } disabled className={ styles.input } />
				<a className={ styles.badge } href={ link } target="_BLANK" rel="noreferrer">
					{ __( 'Paid', 'download-monitor' ) }
				</a>
			</div>
		</div>
	);
}
