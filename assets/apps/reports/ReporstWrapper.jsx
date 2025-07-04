import useStateContext from './context/useStateContext';
export default function ReporstWrapper() {
	const { state } = useStateContext();
	return `start: ${ state.periods.start } end: ${ state.periods.end }`;
}
