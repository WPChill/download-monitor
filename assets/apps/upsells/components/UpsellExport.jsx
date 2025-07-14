import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';
import styles from './UpsellExport.module.scss';

export default function UpsellExport() {
	const link = 'https://www.download-monitor.com/pricing/?utm_source=reports_page&utm_medium=lite-vs-pro&utm_campaign=dlm-enhanced_metrics';

	return (
		<div className={ styles.wrapper }>
			<div className={ styles.input }>
				{ __( 'Export', 'download-monitor' ) }
				<a className={ styles.badge } href={ link } target="_BLANK" rel="noreferrer">
					{ __( 'Paid', 'download-monitor' ) }
				</a>
			</div>
			<div className={ styles.settings } >
				<Dashicon icon="calendar-alt" className={ styles.icon } />
			</div>
		</div>
	);
}
