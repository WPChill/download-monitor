import { __ } from '@wordpress/i18n';
import useStateContext from '../context/useStateContext';
export default function OverviewTab() {
	const { state } = useStateContext();
	return `start: ${ state.periods.start } end: ${ state.periods.end }`;
}
