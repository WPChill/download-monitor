import UpsellExport from './components/UpsellExport';
import UpsellRangePicker from './components/UpsellRangePicker';
import LogsFilter from './components/LogsFilterUpsell';
import { addFilter } from '@wordpress/hooks';
import './index.css';

addFilter(
	'dlm.reports.after.nav',
	'dlm-reports/date-range-upsell',
	( original ) => (
		<>
			{ original }
			<div className="dlm-reports-nav-upsells">
				<UpsellExport />
				<UpsellRangePicker />
			</div>
		</>
	),
);

addFilter(
	'dlm.reports.before.detailedDownloadsTable',
	'dlm-reports/filter-logs-upsell',
	( original ) => (
		<>
			{ original }
			<LogsFilter />
		</>
	),
);
