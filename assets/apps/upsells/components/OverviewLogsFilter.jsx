import { __ } from '@wordpress/i18n';
import styles from './LogsFilter.module.scss';
import { Dashicon } from '@wordpress/components';
import Select from 'react-select';

export default function OverviewLogsFilter() {
	const link = 'https://www.download-monitor.com/pricing/?utm_source=reports_page&utm_medium=lite-vs-pro&utm_campaign=dlm-enhanced_metrics';

	return (
		<>
			<div className={ styles.filtersWrap }>
				<div className={ styles.filters }>
					<div className={ styles.filterGroup }>
						<Select
							className={ styles.select }
							classNamePrefix="dlm-em-upsell-select"
							options={ [] }
							value={ null }
							isClearable={ false }
							isSearchable={ false }
							isDisabled={ true }
							placeholder={ __( 'Filter by download…', 'download-monitor' ) }
						/>
						<a className={ styles.badge } href={ link } target="_BLANK" rel="noreferrer">
							{ __( 'Paid', 'download-monitor' ) }
						</a>
					</div>

					<div className={ styles.filterGroup }>
						<Select
							className={ styles.select }
							classNamePrefix="dlm-em-upsell-select"
							options={ [] }
							value={ null }
							isClearable={ false }
							isSearchable={ false }
							isDisabled={ true }
							placeholder={ __( 'Filter by category…', 'download-monitor' ) }
						/>
						<a className={ styles.badge } href={ link } target="_BLANK" rel="noreferrer">
							{ __( 'Paid', 'download-monitor' ) }
						</a>
					</div>

					<div className={ styles.filterGroup }>
						<Select
							className={ styles.select }
							classNamePrefix="dlm-em-upsell-select"
							options={ [] }
							value={ null }
							isClearable={ false }
							isSearchable={ false }
							isDisabled={ true }
							placeholder={ __( 'Filter by tag…', 'download-monitor' ) }
						/>
						<a className={ styles.badge } href={ link } target="_BLANK" rel="noreferrer">
							{ __( 'Paid', 'download-monitor' ) }
						</a>
					</div>
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
