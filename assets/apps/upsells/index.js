import UpsellExport from './components/UpsellExport';
import UpsellRangePicker from './components/UpsellRangePicker';
import OverviewLogsFilter from './components/OverviewLogsFilter';
import DetailedLogsFilter from './components/DetailedLogsFilter';
import { addFilter } from '@wordpress/hooks';
import './index.css';

addFilter(
	'dlm.reports.after.dateRangeSelect',
	'dlm-reports/date-range-upsell',
	( original ) => (
		<>
			{ original }
			<UpsellRangePicker />
		</>
	),
);

addFilter(
	'dlm.reports.overviewDownloadsTable.header',
	'dlm-reports/overview-filter-logs-upsell',
	( original ) => (
		<>
			{ original }
			<OverviewLogsFilter />
		</>
	),
);
addFilter(
	'dlm.reports.detailedDownloadsTable.header',
	'dlm-reports/filter-logs-upsell',
	( original ) => (
		<>
			{ original }
			<DetailedLogsFilter />
		</>
	),
);
