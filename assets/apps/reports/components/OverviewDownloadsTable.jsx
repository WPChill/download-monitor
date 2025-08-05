import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Spinner, SelectControl } from '@wordpress/components';
import useStateContext from '../context/useStateContext';
import { useGetOverviewTableData } from '../query/useGetTableData';
import styles from './DownloadsTable.module.scss';

import {
	useReactTable,
	getCoreRowModel,
	getPaginationRowModel,
	getSortedRowModel,
	flexRender,
} from '@tanstack/react-table';

export default function OverviewDownloadsTable() {
	const { state, dispatch } = useStateContext();
	const [ pageIndex, setPageIndex ] = useState( 0 );
	const [ pageSize, setPageSize ] = useState( 25 );

	const { data: downloadsData = [], isLoading } = useGetOverviewTableData( state.periods );

	const filteredDownloadsData = useMemo( () => {
		return applyFilters( 'dlm.reports.overviewReportsData', downloadsData, state );
	}, [ downloadsData, state ] );

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

	const visibleColumns = useMemo( () => {
		return columns.filter( ( col ) => {
			if ( ! state.checkedOverviewColumns ) {
				return true;
			}

			return state.checkedOverviewColumns[ col.slug ] !== false;
		} );
	}, [ columns, state.checkedOverviewColumns ] );

	const tableColumns = useMemo( () => {
		return visibleColumns.map( ( col ) => ( {
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
					<div className={ `${ styles.tableHeader } ${ col.sortable ? styles.headerWithSort : '' }` }>
						<span>{ col.title }</span>
						{ sortIconClass && <span className={ `dashicons ${ sortIconClass }` } /> }
					</div>
				);
			},
			cell: ( info ) => {
				const rowData = info.row.original;
				const value = info.getValue();

				return (
					<>
						{ col.slug === 'title' ? (
							<a
								href={ `/wp-admin/post.php?post=${ rowData.download_id }&action=edit` }
								target="_blank"
								rel="noopener noreferrer"
								className={ styles.linkButton }
							>
								{ value }
							</a>
						) : (
							applyFilters( 'dlm.reports.overviewTable.col.' + col.slug, value, { rowData, visibleColumns } )
						) }
					</>
				);
			},
			enableSorting: col.sortable ?? false,
		} ) );
	}, [ visibleColumns ] );

	const table = useReactTable( {
		data: filteredDownloadsData,
		columns: tableColumns,
		state: {
			sorting,
			pagination: {
				pageIndex,
				pageSize,
			},
		},
		onPaginationChange: ( updater ) => {
			const next = typeof updater === 'function' ? updater( { pageIndex } ) : updater;
			if ( next.pageIndex !== pageIndex ) {
				setPageIndex( next.pageIndex );
			}
		},
		onSortingChange: setSorting,
		getCoreRowModel: getCoreRowModel(),
		getSortedRowModel: getSortedRowModel(),
		getPaginationRowModel: getPaginationRowModel(),
		manualPagination: false,
	} );

	const handlePageInputChange = ( e ) => {
		let value = Number( e.target.value );

		if ( isNaN( value ) ) {
			return;
		}

		const maxPage = table.getPageCount();

		if ( value < 1 ) {
			value = 1;
		}
		if ( value > maxPage ) {
			value = maxPage;
		}

		setPageIndex( value - 1 );
	};
	const handlePerPageInputChange = ( size ) => {
		const value = Number( size );

		if ( isNaN( value ) ) {
			return;
		}

		setPageSize( value );
	};
	return (
		<div className={ styles.wrapper }>
			<div className={ styles.header }>
				<h2>Downloads</h2>
				{ applyFilters( 'dlm.reports.overviewDownloadsTable.header', '', { state, dispatch, downloadsData, columns } ) }
			</div>
			<div className={ styles.downloadTableWrapper }>
				<table className={ styles.downloadTable }>
					<thead className={ styles.downloadTableHead }>
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
										className={ `${ styles.tableHeaderCell } dlm-downloads-table-col-${ header.column.id }` }
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
							if ( filteredDownloadsData.length === 0 ) {
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
			</div>
			{ ( filteredDownloadsData.length > 25 || table.getPageCount() > 1 ) && (
				<div className={ styles.pagination }>
					{ table.getPageCount() > 1 && (
						<>
							<span>
								{ __( 'Page', 'download-monitor' ) }
								{ ' ' }
								{ table.getState().pagination.pageIndex + 1 }
								{ ' ' }
								{ __( 'of', 'download-monitor' ) } { table.getPageCount() }
							</span>
							<div className={ styles.paginationButtons }>
								<button
									onClick={ () => table.previousPage() }
									disabled={ ! table.getCanPreviousPage() }
									className={ `${ styles.paginationButton } ${ styles.paginationButtonPrev } dashicons dashicons-arrow-left-alt2` }
								>
								</button>
								<button
									onClick={ () => table.nextPage() }
									disabled={ ! table.getCanNextPage() }
									className={ `${ styles.paginationButton } ${ styles.paginationButtonNext } dashicons dashicons-arrow-right-alt2` }
								>
								</button>
							</div>
							<span className={ styles.pagePicker }>
								{ __( 'Go to page', 'download-monitor' ) }{ ' ' }
								<input
									type="number"
									min="1"
									max={ table.getPageCount() }
									value={ table.getState().pagination.pageIndex + 1 }
									onChange={ handlePageInputChange }
									className={ styles.paginationInput }
								/>
							</span>
						</>
					) }
					{ filteredDownloadsData.length > 25 && (
						<span className={ styles.perPagePicker }>
							{ __( 'Rows per page', 'download-monitor' ) }{ ' ' }
							<SelectControl
								className={ styles.perPageSelect }
								value={ pageSize }
								options={ [
									{ label: '25', value: '25' },
									{ label: '50', value: '50' },
									{ label: '75', value: '75' },
									{ label: '100', value: '100' },
								] }
								onChange={ handlePerPageInputChange }
							/>
						</span>
					) }
				</div>
			) }
			{ applyFilters( 'dlm.reports.after.overviewDownloadsTable', '', { state, dispatch, downloadsData, columns } ) }
		</div>
	);
}
