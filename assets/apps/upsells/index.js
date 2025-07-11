import UpsellRangePicker from './components/UpsellRangePicker';
import { addFilter } from '@wordpress/hooks';

addFilter(
	'dlm.reports.before.dateRangeSelect',
	'dlm-reports/date-range-upsell',
	( original ) => (
		<>
			{ original }
			<UpsellRangePicker />
		</>
	),
);
