import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import CustomDateRangePicker from './CustomDateRangePicker';

addFilter( 'dlm.reports.tabs', 'my-plugin/extend-reports-tabs', function( tabs ) {
	return [
		...tabs,
		{
			title: __( 'Custom Report', 'my-plugin' ),
			slug: 'custom-report',
		},
	];
} );

import { registerFill } from './utils/slotFillRegistry';
console.error( window);

registerFill( 'dlm.reports.tab.custom-report.body', () => (
	<div>
		<h2>Raport Injectat</h2>
		<p>Acesta este un raport extern injectat prin Slot.</p>
	</div>
) );




// registerFill('dlm.card.total.after', ({ cards }) => (
// 	<span style={{ marginLeft: 5, color: 'green' }}>↑10%</span>
// ));

// addFilter(
// 	'dlm.reports.date_range.select',
// 	'my-plugin/custom-date-range-picker',
// 	( original, { dispatch, state } ) => <CustomDateRangePicker dispatch={ dispatch } state={ state } />,
// );
