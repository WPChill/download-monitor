import OverviewCards from './OverviewCards';
import BarChartComponent from './BarChartComponent';
import OverviewDownloadsTable from './OverviewDownloadsTable';
import { applyFilters } from '@wordpress/hooks';
import useStateContext from '../context/useStateContext';
export default function OverviewTab() {
	const { state } = useStateContext();
	return (
		<>
			<OverviewCards />
			<BarChartComponent />
			<OverviewDownloadsTable />
			{ applyFilters( 'dlm.reports.overviewTab', '', { state } ) }
		</>
	);
}
