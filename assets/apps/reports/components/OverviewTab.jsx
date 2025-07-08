import OverviewCards from './OverviewCards';
import BarChart from './BarChart';
import OverviewDownloadsTable from './OverviewDownloadsTable';
export default function OverviewTab() {
	return (
		<>
			<OverviewCards />
			<BarChart />
			<OverviewDownloadsTable />
		</>
	);
}
