import { __ } from '@wordpress/i18n';
import { useGetWidgetChart } from '../query/useGetWidgetChart';
import styles from './BarChartComponent.module.scss';
import { applyFilters } from '@wordpress/hooks';
import { Spinner } from '@wordpress/components';

import {
	ResponsiveContainer,
	BarChart,
	Bar,
	XAxis,
	YAxis,
	Tooltip,
	CartesianGrid,
} from 'recharts';

import { useMemo } from 'react';
import dayjs from 'dayjs';

const start = dayjs().subtract( 30, 'day' ).format( 'YYYY-MM-DD' );
const end = dayjs().format( 'YYYY-MM-DD' );

function generateDateRange() {
	const result = [];
	let current = dayjs( start );
	const last = dayjs( end );

	while ( current.isBefore( last ) || current.isSame( last, 'day' ) ) {
		const label = current.format( 'YYYY-MM-DD' );
		current = current.add( 1, 'day' );

		if ( ! result.includes( label ) ) {
			result.push( label );
		}
	}
	return result;
}

function buildChartData( currentData ) {
	const currentRange = generateDateRange( start, end );

	const currentMap = {};
	currentData.forEach( ( item ) => {
		const d = dayjs( item.date );
		const key = d.format( 'YYYY-MM-DD' );
		currentMap[ key ] = ( currentMap[ key ] || 0 ) + item.downloads;
	} );

	return currentRange.map( ( label, i ) => {
		return {
			date: label,
			current: currentMap[ label ] || 0,
		};
	} );
}

function CustomTooltip( { active, payload, label } ) {
	if ( ! active || ! payload?.length ) {
		return null;
	}

	const current = payload.find( ( p ) => p.dataKey === 'current' )?.value ?? 0;
	const formattedDate = dayjs( label ).format( 'D MMM' );
	return (
		<div className={ styles.tooltip }>
			<div className={ styles.tooltipTitle }>{ __( 'Downloads', 'download-monitor' ) }</div>
			<div className={ styles.tooltipWrap }>
				<div className={ styles.tooltipDataWrap }>
					<span className={ `${ styles.tip } ${ styles.tipCurrent }` } />
					<div className={ styles.tooltipData }>
						<span className={ styles.title }>{ formattedDate }</span>
						<span className={ styles.value }>{ current.toLocaleString() }</span>
					</div>
				</div>
			</div>
		</div>
	);
}

export default function BarChartComponent() {
	const { data, isLoading, error } = useGetWidgetChart();

	const chartData = useMemo( () => {
		if ( ! data?.downloads_data ) {
			return [];
		}

		return buildChartData( data.downloads_data );
	}, [ data ] );

	if ( isLoading ) {
		return (
			<>
				{ applyFilters( 'dlm.widget.chart.before', '', { chartData } ) }
				<div className={ styles.loading }>
					<Spinner className={ styles.spinner } />
				</div>
				{ applyFilters( 'dlm.widget.chart.after', '', { chartData } ) }
			</>
		);
	}
	if ( error || ! chartData.length ) {
		return <p>{ __( 'No chart data available.', 'download-monitor' ) }</p>;
	}

	return (
		<div className={ styles.barChartWrapper }>
			{ applyFilters( 'dlm.widget.chart.before', '', { chartData } ) }
			<div className={ styles.barChart }>
				<ResponsiveContainer height={ 200 }>
					<BarChart 
						className={ styles.barChartTable }
						onClick={ () => { 
							window.location.href = '/wp-admin/edit.php?post_type=dlm_download&page=download-monitor-reports&range=last30days';
						}}
						data={ chartData }>
						<CartesianGrid horizontal={ true } vertical={ false } stroke="#f0f0f0" />
						<XAxis
							dataKey="date"
							tick={ { fontSize: 12, fontWeight: 700 } }
							tickFormatter={ ( value ) => dayjs( value ).format( 'D MMM' ) }
						/>
						<YAxis />
						{ applyFilters( 'dlm.widget.chart.tooltip',
							<Tooltip cursor={ { fill: 'rgba(0, 0, 0, 0.1)' } } content={ <CustomTooltip /> } />,
						) }
						<Bar dataKey="current" stackId="currentDownloads" fill="#31688e" name={ __( 'Current', 'download-monitor' ) } />
						{ applyFilters( 'dlm.widget.chart', '' ) }
					</BarChart>
				</ResponsiveContainer>
			</div>
			{ applyFilters( 'dlm.widget.chart.after', '', { chartData } ) }
		</div>
	);
}
