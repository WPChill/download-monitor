import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Spinner } from '@wordpress/components';
import useStateContext from '../context/useStateContext';
import { useGetTableData } from '../query/useGetTableData';
import styles from './OverviewDownloadsTable.module.scss';
import {
	useReactTable,
	getCoreRowModel,
	getPaginationRowModel,
	getSortedRowModel,
	flexRender,
} from '@tanstack/react-table';
import Slot from './Slot';

export default function OverviewDownloadsTable() {
	const { state } = useStateContext();
	const { data: downloadsData = [], isLoading } = useGetTableData( state.periods );

	const [ sorting, setSorting ] = useState( [
		{ id: 'total', desc: true },
	] );

	const columns = useMemo( () => {
		const baseColumns = [
			{ title: __( 'ID', 'download-monitor' ), slug: 'download_id', sortable: false },
			{ title: __( 'Title', 'download-monitor' ), slug: 'title', sortable: true },
			{ title: __( 'Total', 'download-monitor' ), slug: 'total', sortable: true },
		];
		return applyFilters( 'dlm.reports.overview.table', baseColumns );
	}, [] );

	const tableColumns = useMemo( () => {
		return columns.map( ( col ) => ( {
			id: col.slug,
			accessorKey: col.slug,
			header: ( headerContext ) => {
				const sortState = headerContext.column.getIsSorted();
				let sortIconClass = '';

				if ( sortState === 'asc' ) {
					sortIconClass = 'dashicons-arrow-up-alt2';
				} else if ( sortState === 'desc' ) {
					sortIconClass = 'dashicons-arrow-down-alt2';
				}

				return (
					<div className={ styles.headerWithSort }>
						<span>{ col.title }</span>
						{ sortIconClass && <span className={ `dashicons ${ sortIconClass }` } /> }
					</div>
				);
			},
			cell: ( info ) => (
				<>
					{ col.slug === 'title' ? (
						<button
							type="button"
							className={ styles.linkButton }
						>
							{ info.getValue() }
						</button>
					) : (
						info.getValue()
					) }
					<span id={ `dlm-chart-slot-${ col.slug }` } />
					<Slot
						name={ `dlm.chart.${ col.slug }` }
						containerId={ `dlm-chart-slot-${ col.slug }` }
						chartData={ info.row.original }
					/>
				</>
			),
			enableSorting: col.sortable ?? false,
		} ) );
	}, [ columns, sorting ] );

	const table = useReactTable( {
		data: downloadsData,
		columns: tableColumns,
		state: {
			sorting,
		},
		onSortingChange: setSorting,
		getCoreRowModel: getCoreRowModel(),
		getSortedRowModel: getSortedRowModel(),
		getPaginationRowModel: getPaginationRowModel(),
		pageSize: 10,
	} );

	const pageIndex = table.getState().pagination.pageIndex;
	const totalPages = table.getPageCount();
	const [ pageInput, setPageInput ] = useState( pageIndex + 1 );

	const goToPage = ( e ) => {
		const newPage = Number( e.target.value ) - 1;
		if ( ! isNaN( newPage ) && newPage >= 0 && newPage < totalPages ) {
			table.setPageIndex( newPage );
			setPageInput( newPage + 1 );
		}
	};

	return (
		<div className={ styles.downloadTableWrapper }>
			<table className={ styles.downloadTable }>
				<thead>
					{ table.getHeaderGroups().map( ( headerGroup ) => (
						<tr key={ headerGroup.id } className={ styles.tableHeaderRow }>
							{ headerGroup.headers.map( ( header ) => (
								<th
									key={ header.id }
									onClick={
										header.column.getCanSort()
											? () => {
												const isSorted = header.column.getIsSorted();
												const nextSort = isSorted === 'desc' ? 'asc' : 'desc';
												setSorting( [ { id: header.column.id, desc: nextSort === 'desc' } ] );
											}
											: undefined
									}
									className={ styles.tableHeaderCell }
								>
									{ flexRender( header.column.columnDef.header, header.getContext() ) }
								</th>
							) ) }
						</tr>
					) ) }
				</thead>
				<tbody>
					{ ( () => {
						if ( isLoading ) {
							return (
								<tr>
									<td colSpan={ columns.length } className={ styles.tableLoadingCell }>
										<Spinner />
									</td>
								</tr>
							);
						}
						if ( downloadsData.length === 0 ) {
							return (
								<tr>
									<td colSpan={ columns.length } className={ styles.tableLoadingCell }>
										{ __( 'No downloads found.', 'download-monitor' ) }
									</td>
								</tr>
							);
						}
						return table.getRowModel().rows.map( ( row ) => (
							<tr key={ row.id } className={ styles.tableRow }>
								{ row.getVisibleCells().map( ( cell ) => (
									<td key={ cell.id } className={ styles.tableCell }>
										{ flexRender( cell.column.columnDef.cell, cell.getContext() ) }
									</td>
								) ) }
							</tr>
						) );
					} )() }
				</tbody>
			</table>

			<div className={ styles.pagination }>
				<button
					onClick={ () => table.previousPage() }
					disabled={ ! table.getCanPreviousPage() }
					className={ styles.paginationButton }
				>
					{ __( 'Previous', 'download-monitor' ) }
				</button>
				<span>
					{ __( 'Page', 'download-monitor' ) }{ ' ' }
					<input
						type="number"
						min="1"
						max={ totalPages }
						value={ pageInput }
						onChange={ ( e ) => setPageInput( e.target.value ) }
						onBlur={ goToPage }
						className={ styles.paginationInput }
					/>{ ' ' }
					{ __( 'of', 'download-monitor' ) } { totalPages }
				</span>
				<button
					onClick={ () => table.nextPage() }
					disabled={ ! table.getCanNextPage() }
					className={ styles.paginationButton }
				>
					{ __( 'Next', 'download-monitor' ) }
				</button>
			</div>

		</div>
	);
}
