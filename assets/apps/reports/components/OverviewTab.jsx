import OverviewCards from './OverviewCards';
import BarChart from './BarChart';
import OverviewDownloadsTable from './OverviewDownloadsTable';
import { applyFilters } from '@wordpress/hooks';
import useStateContext from '../context/useStateContext';
export default function OverviewTab() {
	const { state } = useStateContext();
	return (
		<>
			<OverviewCards />
			<BarChart />
			<OverviewDownloadsTable />
			{ applyFilters( 'dlm.reports.overviewTab', '', { state } ) }
		</>
	);
}
