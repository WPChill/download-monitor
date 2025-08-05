import DetailedCards from './DetailedCards';
import DetailedDownloadsTable from './DetailedDownloadsTable';
import { applyFilters } from '@wordpress/hooks';
import useStateContext from '../context/useStateContext';
import { useGetUserData } from '../query/useGetUserData';
export default function DetailedTab() {
	const { state, dispatch } = useStateContext();
	const { data: usersData = [], isLoadingUsers } = useGetUserData( state.periods );
	return (
		<>
			<DetailedCards />
			<DetailedDownloadsTable usersData={ usersData } isLoadingUsers={ isLoadingUsers } />
			{ applyFilters( 'dlm.reports.detailedTab', '', { state, dispatch, usersData, isLoadingUsers } ) }
		</>
	);
}
