import UpsellExport from './components/UpsellExport';
import UpsellRangePicker from './components/UpsellRangePicker';
import LogsFilter from './components/LogsFilterUpsell';
import { addFilter } from '@wordpress/hooks';

addFilter(
	'dlm.reports.before.dateRangeSelect',
	'dlm-reports/date-range-upsell',
	( original ) => (
		<>
			{ original }
			<UpsellExport />
			<UpsellRangePicker />
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
