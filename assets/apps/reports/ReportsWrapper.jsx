import useStateContext from './context/useStateContext';
import DateRangeSelect from './components/DateRangeSelect';
import TabNavigation from './components/TabNavigation';
import OverviewTab from './components/OverviewTab';
import DetailedTab from './components/DetailedTab';
import { applyFilters } from '@wordpress/hooks';
import styles from './ReportsWrapper.module.scss';

export default function ReportsWrapper() {
	const { state, dispatch } = useStateContext();

	const renderTabContent = () => {
		switch (state.activeTab) {
			case 'overview':
				return (<OverviewTab />);
			case 'detailed':
				return (<DetailedTab />);
			default:
				return applyFilters(`dlm.reports.tab.${state.activeTab}.body`, '', { dispatch, state });
		}
	};

	return (
		<div className={styles.dlmReportsWrapper} >
			<div className={styles.dlmReportsHeader}>
				<TabNavigation />
				{applyFilters('dlm.reports.after.nav', '', { dispatch, state })}
			</div>
			<div className={styles.dlmReportsRangeSelect}>
				<DateRangeSelect />
				{applyFilters('dlm.reports.after.rangeSelect', '', { dispatch, state })}
			</div>
			<div className={styles.dlmReportsBody}>
				{renderTabContent()}
				{applyFilters('dlm.reports.after.tab.content', '', { dispatch, state })}
			</div>
		</div>
	);
}
