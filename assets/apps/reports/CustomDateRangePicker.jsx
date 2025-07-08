import { useState, useEffect } from '@wordpress/element';
import { DateRange } from 'react-date-range';
import { __ } from '@wordpress/i18n';
import 'react-date-range/dist/styles.css';
import 'react-date-range/dist/theme/default.css';

export default function CustomDateRangePicker( { dispatch, state } ) {

	const [ range, setRange ] = useState( [
		{
			startDate: state.periods?.start ? new Date( state.periods.start ) : new Date(),
			endDate: state.periods?.end ? new Date( state.periods.end ) : new Date(),
			key: 'selection',
		},
	] );

	useEffect( () => {
		if ( state.periods?.start && state.periods?.end ) {
			setRange( [
				{
					startDate: new Date( state.periods.start ),
					endDate: new Date( state.periods.end ),
					key: 'selection',
				},
			] );
		}
	}, [ state.periods ] );

	const handleSelect = ( ranges ) => {
		const selection = ranges.selection;
		setRange( [ selection ] );

		dispatch( {
			type: 'SET_PERIODS',
			payload: {
				start: selection.startDate.toISOString().slice( 0, 10 ),
				end: selection.endDate.toISOString().slice( 0, 10 ),
			},
		} );
	};

	return (
		<div>
			<label>{ __( 'Select Period', 'download-monitor' ) }</label>
			<DateRange
				ranges={ range }
				onChange={ handleSelect }
				moveRangeOnFirstSelection={ false }
				editableDateInputs={ true }
				months={ 1 }
				direction="horizontal"
			/>
		</div>
	);
}
