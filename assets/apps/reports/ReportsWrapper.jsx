import useStateContext from './context/useStateContext';
import DateRangeSelect from './components/DateRangeSelect';
import TabNavigation from './components/TabNavigation';
import OverviewTab from './components/OverviewTab';
import DetailedTab from './components/DetailedTab';
import styles from './ReportsWrapper.module.scss';
import Slot from './components/Slot';

export default function ReportsWrapper() {
	const { state } = useStateContext();

	const renderTabContent = () => {
		switch ( state.activeTab ) {
			case 'overview':
				return (
					<>
						<OverviewTab />
						<div id="dlm-tab-slot-overview" />
						<Slot name="dlm.reports.tab.overview.body" containerId="dlm-tab-slot-overview" />
					</>
				);
			case 'detailed':
				return (
					<>
						<DetailedTab />
						<div id="dlm-tab-slot-detailed" />
						<Slot name="dlm.reports.tab.detailed.body" containerId="dlm-tab-slot-detailed" />
					</>
				);
			default:
				return (
					<>
						<div id="dlm-tab-slot" />
						<Slot name={ `dlm.reports.tab.${ state.activeTab }.body` } containerId="dlm-tab-slot" />
					</>
				);
		}
	};

	return (
		<div className={ styles.dlmReportsWrapper } >
			<div className={ styles.dlmReportsHeader }>
				<TabNavigation />
				<DateRangeSelect />
			</div>
			<div className={ styles.dlmReportsBody }>
				{ renderTabContent() }
			</div>
		</div>
	);
}
