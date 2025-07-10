import { __ } from '@wordpress/i18n';
import useStateContext from '../context/useStateContext';
import { useGetChartData } from '../query/useGetChartData';
import styles from './BarChart.module.scss';
import { Spinner, Icon } from '@wordpress/components';
import { arrowUp, arrowDown } from '@wordpress/icons';
import { dateI18n, getSettings } from '@wordpress/date';
import Slot from './Slot';

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

import { useMemo } from 'react';
import dayjs from 'dayjs';

const { formats } = getSettings();

function generateDateRange( start, end ) {
	const result = [];
	let current = dayjs( start );
	const last = dayjs( end );

	while ( current.isBefore( last ) || current.isSame( last, 'day' ) ) {
		result.push( current.format( 'YYYY-MM-DD' ) );
		current = current.add( 1, 'day' );
	}
	return result;
}

function buildChartData( currentData, compareData, periods ) {
	const range = generateDateRange( periods.start, periods.end );
	const currentMap = Object.fromEntries( currentData.map( ( item ) => [ item.date, item.downloads ] ) );

	const compareValues = compareData?.map( ( item ) => item.downloads ) ?? [];

	return range.map( ( date, index ) => ( {
		date,
		current: currentMap[ date ] || 0,
		compare: compareValues[ index ] ?? 0,
		compare_date: compareData?.[ index ]?.date ?? null,
	} ) );
}

function CustomTooltip( { active, payload, label } ) {
	if ( ! active || ! payload?.length ) {
		return null;
	}

	const current = payload.find( ( p ) => p.dataKey === 'current' )?.value ?? 0;
	const comparePayload = payload.find( ( p ) => p.dataKey === 'compare' );
	const compare = comparePayload?.value;

	let diff = null;
	let diffValue = 0;
	let diffDirection = null;

	if ( typeof compare === 'number' ) {
		if ( compare !== 0 ) {
			diffValue = ( ( current - compare ) / compare ) * 100;
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

	const compareDate = payload[ 0 ]?.payload?.compare_date;

	return (
		<div className={ styles.tooltip }>
			<table className={ styles.tooltipTable }>
				<thead>
					<tr>
						<th className={ styles.tooltipDate }>
							<strong>{ dateI18n( formats.date, new Date( label + 'T12:00:00' ) ) }</strong>
						</th>
						{ typeof compare === 'number' && (
							<th className={ styles.tooltipDate }>
								<strong>{ dateI18n( formats.date, new Date( compareDate + 'T12:00:00' ) ) }</strong>
							</th>
						) }
					</tr>
				</thead>
				<tbody>
					<tr>
						<td className={ styles.tooltipData }>
							{ current } { __( 'downloads', 'download-monitor' ) }
						</td>
						{ typeof compare === 'number' && (
							<td className={ styles.tooltipData }>
								{ compare } { __( 'downloads', 'download-monitor' ) }
							</td>
						) }
					</tr>
					<tr>
						{ typeof compare === 'number' && (
							<td colSpan={ 2 } >
								<div
									colSpan={ 2 }
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
							</td>
						) }
					</tr>
				</tbody>
			</table>
		</div>
	);
}

export default function Chart() {
	const { state } = useStateContext();
	const { data, isLoading, error } = useGetChartData( state.periods );

	const chartData = useMemo( () => {
		if ( ! data?.downloads_data ) {
			return [];
		}
		return buildChartData( data.downloads_data, data.compare_data, state.periods );
	}, [ data, state.periods ] );

	const hasCompare = useMemo( () => !! ( data?.compare_data?.length ), [ data ] );

	if ( isLoading ) {
		return <Spinner />;
	}
	if ( error || ! chartData.length ) {
		return <p>{ __( 'No chart data available.', 'download-monitor' ) }</p>;
	}

	return (
		<div className={ styles.barChartWrapper }>
			<ResponsiveContainer width="100%" height={ 400 }>
				<BarChart data={ chartData }>
					<CartesianGrid stroke="#e0e0e0" strokeDasharray="3 3" />
					<XAxis
						dataKey="date"
						tick={ { fontSize: 12, fontWeight: 700 } }
						tickFormatter={ ( value ) => dateI18n( formats.date, new Date( value + 'T12:00:00' ) ) }
					/>
					<YAxis />
					<Legend
						verticalAlign="top"
						align="center"
						layout="horizontal"
						iconType="circle"
					/>
					<Tooltip content={ <CustomTooltip /> } />
					<Bar dataKey="current" stackId="downloads" fill="#4a7aff" name={ __( 'Current', 'download-monitor' ) } />
					{ hasCompare && (
						<Bar dataKey="compare" stackId="downloads" fill="rgba(100, 100, 255, 0.3)" name={ __( 'Compare', 'download-monitor' ) } />
					) }
				</BarChart>
			</ResponsiveContainer>
			<span id="dlm-chart-slot-after" />
			<Slot name="dlm.chart.after" containerId="dlm-chart-slot-after" chartData={ chartData } />
		</div>
	);
}
