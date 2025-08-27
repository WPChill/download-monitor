import { applyFilters } from '@wordpress/hooks';
import BarChartComponent from './components/BarChartComponent';
import WidgetCards from './components/WidgetCards';
import styles from './ReportsWrapper.module.scss';

export default function ReportsWrapper() {
	return (
		<div className={ styles.dlmReportsWrapper } >
			<BarChartComponent />
			<WidgetCards />
			{ applyFilters( 'dlm.reports.overviewTab', '' ) }
		</div>
	);
}
