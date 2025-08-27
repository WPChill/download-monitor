import { __ } from '@wordpress/i18n';
import styles from './LogsFilter.module.scss';
import { Dashicon } from '@wordpress/components';

export default function OverviewLogsFilter() {
	const link = 'https://www.download-monitor.com/pricing/?utm_source=reports_page&utm_medium=lite-vs-pro&utm_campaign=dlm-enhanced_metrics';

	return (
		<>
			<div className={ styles.wrapper }>
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
			<div className={ styles.right }>
				<div className={ styles.wrapp }>
					<div className={ styles.button } >
						<Dashicon icon="cloud" className={ styles.icon } /> { __( 'Export CSV', 'dlm-enhanced-metrics' ) }
					</div>
				</div>
				<a className={ styles.badge } href={ link } target="_BLANK" rel="noreferrer">
					{ __( 'Paid', 'download-monitor' ) }
				</a>
			</div>
		</>
	);
}
