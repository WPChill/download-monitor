import { useMemo, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import useStateContext from '../context/useStateContext';
import styles from './DateRangeSelect.module.scss';
import Select from 'react-select';
import dayjs from 'dayjs';
import { setPeriods } from '../context/actions';

export default function DateRangeSelect() {
	const { state, dispatch } = useStateContext();

	const options = useMemo( () => {
		const now = dayjs();
		return [
			{
				label: __( 'Last 7 Days', 'download-monitor' ),
				value: 'last7days',
				start: now.subtract( 7, 'day' ).format( 'YYYY-MM-DD' ),
				end: now.format( 'YYYY-MM-DD' ),
			},
			{
				label: __( 'Last Month', 'download-monitor' ),
				value: 'lastMonth',
				start: now.subtract( 1, 'month' ).startOf( 'month' ).format( 'YYYY-MM-DD' ),
				end: now.subtract( 1, 'month' ).endOf( 'month' ).format( 'YYYY-MM-DD' ),
			},
			{
				label: __( 'Last 30 Days', 'download-monitor' ),
				value: 'last30days',
				start: now.subtract( 30, 'day' ).format( 'YYYY-MM-DD' ),
				end: now.format( 'YYYY-MM-DD' ),
			},
		];
	}, [] );

	const getUrlParam = ( key ) => {
		const urlParams = new URLSearchParams( window.location.search );
		return urlParams.get( key );
	};

	const rangeFromUrl = getUrlParam( 'range' );

	const initialOption = useMemo( () => {
		if ( rangeFromUrl ) {
			const match = options.find( ( opt ) => opt.value === rangeFromUrl );
			return match || options[ 0 ];
		}
		return options[ 0 ];
	}, [ options, rangeFromUrl ] );

	const [ selectedOption, setSelectedOption ] = useState( initialOption );

	useEffect( () => {
		if ( initialOption?.start && initialOption?.end ) {
			dispatch( setPeriods( { start: initialOption.start, end: initialOption.end } ) );
		}
	}, [ initialOption, dispatch ] );

	const handleChange = ( selected ) => {
		setSelectedOption( selected );
		if ( selected?.start && selected?.end ) {
			dispatch( setPeriods( { start: selected.start, end: selected.end } ) );
		}
	};

	const DefaultSelect = (
		<div className={ styles.dateRangeSelectWrapp }>
			{ applyFilters( 'dlm.reports.before.dateRangeSelect', '', { dispatch, state } ) }
			<div className={ styles.dateRangeSelect }>
				<Select
					options={ options }
					value={ selectedOption }
					getOptionLabel={ ( option ) => option.label }
					getOptionValue={ ( option ) => option.value }
					onChange={ handleChange }
					classNamePrefix="dlm-date-range-select"
					isSearchable={ false }
				/>
			</div>
			{ applyFilters( 'dlm.reports.after.dateRangeSelect', '', { dispatch, state } ) }
		</div>
	);

	const PeriodPicker = applyFilters(
		'dlm.reports.date_range.select',
		DefaultSelect,
		{ options, dispatch, state },
	);

	return PeriodPicker;
}
