import { __ } from '@wordpress/i18n';
import useStateContext from '../context/useStateContext';
import { useGetChartData } from '../query/useGetChartData';
import styles from './BarChart.module.scss';
import { applyFilters } from '@wordpress/hooks';
import { Spinner, Icon, Button } from '@wordpress/components';
import { arrowUp, arrowDown } from '@wordpress/icons';
import { dateI18n, getSettings } from '@wordpress/date';
import {
	ResponsiveContainer,
	BarChart,
	Bar,
	XAxis,
	YAxis,
	Tooltip,
	Legend,
	CartesianGrid,
} from 'recharts';

import { useMemo, useState } from 'react';
import dayjs from 'dayjs';

const { formats } = getSettings();

function generateDateRange( start, end, groupBy = 'days' ) {
	const result = [];
	let current = dayjs( start );
	const last = dayjs( end );

	while ( current.isBefore( last ) || current.isSame( last, 'day' ) ) {
		let label;
		if ( groupBy === 'days' ) {
			label = current.format( 'YYYY-MM-DD' );
			current = current.add( 1, 'day' );
		} else if ( groupBy === 'weeks' ) {
			label = current.startOf( 'week' ).format( 'YYYY-MM-DD' );
			current = current.add( 1, 'week' );
		} else if ( groupBy === 'months' ) {
			label = current.format( 'YYYY-MM' );
			current = current.add( 1, 'month' );
		}
		if ( ! result.includes( label ) ) {
			result.push( label );
		}
	}
	return result;
}

function buildChartData( currentData, compareData, periods, groupBy ) {
	const currentRange = generateDateRange( periods.start, periods.end, groupBy );
	const compareRange = periods.compare_start && periods.compare_end
		? generateDateRange( periods.compare_start, periods.compare_end, groupBy )
		: [];

	const getKey = ( date ) => {
		const d = dayjs( date );
		if ( groupBy === 'days' ) {
			return d.format( 'YYYY-MM-DD' );
		}
		if ( groupBy === 'weeks' ) {
			return d.startOf( 'week' ).format( 'YYYY-MM-DD' );
		}
		if ( groupBy === 'months' ) {
			return d.format( 'YYYY-MM' );
		}
	};

	const currentMap = {};
	currentData.forEach( ( item ) => {
		const key = getKey( item.date );
		currentMap[ key ] = ( currentMap[ key ] || 0 ) + item.downloads;
	} );

	const compareMap = {};
	compareData?.forEach( ( item ) => {
		const key = getKey( item.date );
		compareMap[ key ] = ( compareMap[ key ] || 0 ) + item.downloads;
	} );

	return currentRange.map( ( label, i ) => {
		const compareLabel = compareRange[ i ];
		return {
			date: label,
			current: currentMap[ label ] || 0,
			compare: compareLabel ? ( compareMap[ compareLabel ] || 0 ) : 0,
		};
	} );
}

function CustomTooltip( { active, payload } ) {
	if ( ! active || ! payload?.length ) {
		return null;
	}

	const current = payload.find( ( p ) => p.dataKey === 'current' )?.value ?? 0;
	const compare = payload.find( ( p ) => p.dataKey === 'compare' )?.value;

	let diff = null;
	let diffDirection = null;

	if ( typeof compare === 'number' ) {
		if ( compare !== 0 ) {
			const diffValue = ( ( current - compare ) / compare ) * 100;
			diff = `${ diffValue.toFixed( 1 ) }%`;
			if ( diffValue > 0 ) {
				diffDirection = 'up';
			} else if ( diffValue < 0 ) {
				diffDirection = 'down';
			} else {
				diffDirection = null;
			}
		} else if ( current !== 0 ) {
			diff = '∞';
			diffDirection = 'up';
		}
	}

	return (
		<div className={ styles.tooltip }>
			<div className={ styles.tooltipTitle }>{ __( 'Downloads', 'download-monitor' ) }</div>
			<div className={ styles.tooltipWrap }>
				<div className={ styles.tooltipDataWrap }>
					<span className={ `${ styles.tip } ${ styles.tipCurrent }` } />
					<div className={ styles.tooltipData }>
						<span className={ styles.title }>{ __( 'Current', 'download-monitor' ) }</span>
						<span className={ styles.value }>{ current }</span>
					</div>
				</div>
				{ typeof compare === 'number' && (
					<>
						<div className={ styles.tooltipDataWrap }>
							<span className={ `${ styles.tip } ${ styles.tipCompare }` } />
							<div className={ styles.tooltipData }>
								<span className={ styles.title }>{ __( 'Compare', 'download-monitor' ) }</span>
								<span className={ styles.value }>{ compare }</span>
							</div>
						</div>
						<div
							className={ `
								${ styles.diff }
								${ diffDirection === 'up' ? styles.increase : '' }
								${ diffDirection === 'down' ? styles.decrease : '' }
							`.trim() }
						>
							{ diffDirection === 'up' && <Icon icon={ arrowUp } /> }
							{ diffDirection === 'down' && <Icon icon={ arrowDown } /> }
							{ diff }
						</div>
					</>
				) }
			</div>
		</div>
	);
}

export default function Chart() {
	const { state } = useStateContext();
	const { data, isLoading, error } = useGetChartData( state.periods );
	const [ groupBy, setGroupBy ] = useState( 'days' );

	const rangeInDays = dayjs( state.periods.end ).diff( dayjs( state.periods.start ), 'day' ) + 1;

	const canGroupBy = {
		days: true,
		weeks: rangeInDays >= 7,
		months: rangeInDays >= 28,
	};

	const chartData = useMemo( () => {
		if ( ! data?.downloads_data ) {
			return [];
		}
		return buildChartData( data.downloads_data, data.compare_data, state.periods, groupBy );
	}, [ data, state.periods, groupBy ] );

	const hasCompare = useMemo( () => !! data?.compare_data?.length, [ data ] );

	if ( isLoading ) {
		return <Spinner />;
	}
	if ( error || ! chartData.length ) {
		return <p>{ __( 'No chart data available.', 'download-monitor' ) }</p>;
	}

	return (
		<div className={ styles.barChartWrapper }>
			<div className={ styles.groupByWrap }>
				{ __( 'Group by', 'download-monitor' ) }
				{ [ 'days', 'weeks', 'months' ]
					.filter( ( key ) => canGroupBy[ key ] )
					.map( ( key ) => (
						<Button
							key={ key }
							variant="link"
							onClick={ () => setGroupBy( key ) }
							className={ `
							${ styles.groupByButton }
							${ groupBy === key ? styles.activeGroupBy : '' }
						`.trim() }
						>
							{ {
								days: __( 'days', 'download-monitor' ),
								weeks: __( 'weeks', 'download-monitor' ),
								months: __( 'months', 'download-monitor' ),
							}[ key ] }
						</Button >
					) ) }
			</div>

			<ResponsiveContainer width="100%" height={ 400 }>
				<BarChart data={ chartData }>
					<CartesianGrid stroke="#e0e0e0" strokeDasharray="3 3" />
					<XAxis
						dataKey="date"
						tick={ { fontSize: 12, fontWeight: 700 } }
						tickFormatter={ ( value ) => dateI18n( formats.date, new Date( value + 'T12:00:00' ) ) }
					/>
					<YAxis />
					<Legend verticalAlign="top" align="center" layout="horizontal" iconType="circle" />
					<Tooltip content={ <CustomTooltip /> } />
					<Bar dataKey="current" stackId="downloads" fill="#4a7aff" name={ __( 'Current', 'download-monitor' ) } />
					{ hasCompare && (
						<Bar dataKey="compare" stackId="downloads" fill="rgba(100, 100, 255, 0.3)" name={ __( 'Compare', 'download-monitor' ) } />
					) }
				</BarChart>
			</ResponsiveContainer>
			{ applyFilters( 'dlm.overview.chart.after', '', { state, chartData } ) }
		</div>
	);
}
