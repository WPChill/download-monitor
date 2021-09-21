jQuery( function ( $ ) {

	// init chart blocks
	$.each( $( '.dlm-reports-block-chart' ), function ( k, v ) {
		new DLM_Reports_Block_Chart( v );
	} );

	$.each( $( '.dlm-reports-block-summary' ), function ( k, v ) {
		new DLM_Reports_Block_Summary( v );
	} );

	$.each( $( '.dlm-reports-block-table' ), function ( k, v ) {
		new DLM_Reports_Block_Table( v );
	} );

	$( '#total_downloads_browser_table' ).on( 'click', 'a', function ( e ) {
		e.preventDefault();

		var target = $( this ).attr( 'href' );
		$( this ).addClass( 'nav-tab-active' );
		$( '#total_downloads_browser_table' ).find( 'a' ).not( $( this ) ).removeClass( 'nav-tab-active' );
		$( target ).removeClass( 'hidden' );
		$( '#total_downloads_browser_table' ).find( 'table' ).not( $( target ) ).addClass( 'hidden' );
	} );

} );

/**
 * Creates a loader obj used in report blocks
 *
 * @returns {Element}
 * @constructor
 */
function DLM_createLoaderObj() {
	var loaderObj = document.createElement( "div" );
	loaderObj = jQuery( loaderObj );
	loaderObj.addClass( 'dlm_reports_loader' );

	var loaderImgObj = document.createElement( "img" );
	loaderImgObj = jQuery( loaderImgObj );
	loaderImgObj.attr( 'src', dlm_rs.img_path + 'ajax-loader.gif' );

	loaderObj.append( loaderImgObj );

	return loaderObj;
}

/**
 * DLM_Reports_Data
 *
 * @param el
 * @constructor
 */
var DLM_Reports_Data = function ( el ) {
	this.type = null;
	this.from = null;
	this.to = null;
	this.period = null;

	this.init = function ( el ) {
		this.type = jQuery( el ).data( 'type' );
		this.to = jQuery( el ).data( 'to' );
		this.from = jQuery( el ).data( 'from' );
		this.period = jQuery( el ).data( 'period' );
	};
	this.init( el );
};

/**
 * DLM_Reports_Data_Fetch
 *
 * @param id
 * @param data
 * @param cb
 * @constructor
 */
var DLM_Reports_Data_Fetch = function ( id, data, cb ) {
	this.id = id;
	this.data = data;
	this.cb = cb;
	this.fetch();
};

DLM_Reports_Data_Fetch.prototype.fetch = function () {
	var id = this.id;
	var cb = this.cb;
	var from = this.data.from;
	var to = this.data.to;
	var period = this.data.period;
	jQuery.get( ajaxurl, {
		action: 'dlm_reports_data',
		nonce: dlm_rs.ajax_nonce,
		id: id,
		from: from,
		to: to,
		period: period
	}, function ( response ) {
		cb( response );
	} );
};

/**
 * DLM_Reports_Block_Chart
 *
 * @param c
 * @constructor
 */
var DLM_Reports_Block_Chart = function ( c ) {

	this.container = c;
	this.id = null;

	this.queryData = null;

	this.data = null;
	this.chart = null;

	this.setup = function () {
		this.id = jQuery( this.container ).attr( 'id' );
		this.queryData = new DLM_Reports_Data( this.container );
		this.displayLoader();
		this.fetch();
	};

	this.setup();

};

DLM_Reports_Block_Chart.prototype.displayLoader = function () {
	jQuery( this.container ).append( DLM_createLoaderObj() );
};

DLM_Reports_Block_Chart.prototype.hideLoader = function () {
	jQuery( this.container ).find( '.dlm_reports_loader' ).remove();
};

DLM_Reports_Block_Chart.prototype.fetch = function () {
	var instance = this;
	new DLM_Reports_Data_Fetch( this.id, this.queryData, function ( response ) {
		instance.data = response;
		instance.hideLoader();
		instance.render();
	} );
};

DLM_Reports_Block_Chart.prototype.render = function () {
	if ( this.data === null ) {
		return;
	}
	var chartId = document.getElementById('total_downloads_chart');
	this.chart = new Chart( chartId, {
		title: "",
		data: this.data,
		type: this.queryData.type,
		height: 250,
		show_dots: 0,
		x_axis_mode: "tick",
		y_axis_mode: "span",
		is_series: 1,
	} );
};

/**
 * DLM_Reports_Block_Summary
 *
 * @param c
 * @constructor
 */
var DLM_Reports_Block_Summary = function ( c ) {

	this.container = c;
	this.id = null;

	this.data = null;

	this.data = null;
	this.chart = null;

	this.setup = function () {
		this.id = jQuery( this.container ).attr( 'id' );
		this.data = new DLM_Reports_Data( this.container );
		this.displayLoader();
		this.fetch();
	};

	this.setup();

};

DLM_Reports_Block_Summary.prototype.displayLoader = function () {
	jQuery( this.container ).append( DLM_createLoaderObj() );
};

DLM_Reports_Block_Summary.prototype.hideLoader = function () {
	jQuery( this.container ).find( '.dlm_reports_loader' ).remove();
};

DLM_Reports_Block_Summary.prototype.fetch = function () {
	var instance = this;
	new DLM_Reports_Data_Fetch( this.id, this.data, function ( response ) {
		instance.data = response;
		instance.hideLoader();
		instance.render();
	} );
};

DLM_Reports_Block_Summary.prototype.render = function () {
	if ( this.data === null ) {
		return;
	}

	var instance = this;

	jQuery.each( this.data, function ( k, v ) {
		if ( jQuery( instance.container ).find( '#' + k ) ) {
			jQuery( instance.container ).find( '#' + k ).find( 'span:first' ).html( v );
		}
	} );
};

/**
 * DLM_Reports_Block_Table
 *
 * @param c
 * @constructor
 */
var DLM_Reports_Block_Table = function ( c ) {

	this.container = c;
	this.id = null;

	this.data = null;

	this.data = null;
	this.chart = null;

	this.setup = function () {
		this.id = jQuery( this.container ).attr( 'id' );
		this.data = new DLM_Reports_Data( this.container );
		this.displayLoader();
		this.fetch();
	};

	this.setup();

};

DLM_Reports_Block_Table.prototype.displayLoader = function () {
	jQuery( this.container ).append( DLM_createLoaderObj() );
};

DLM_Reports_Block_Table.prototype.hideLoader = function () {
	jQuery( this.container ).find( '.dlm_reports_loader' ).remove();
};

DLM_Reports_Block_Table.prototype.fetch = function () {
	var instance = this;
	new DLM_Reports_Data_Fetch( this.id, this.data, function ( response ) {
		instance.data = response;
		instance.hideLoader();
		instance.render();
	} );
};

DLM_Reports_Block_Table.prototype.render = function () {
	if ( this.data === null || (this.data.length < 2 && 'undefined' === typeof this.data['total_downloads_browser_table']) ) {
		return;
	}

	var instance = this;

	if ( 'undefined' !== typeof this.data['total_downloads_browser_table'] ) {

		var $data = this.data['total_downloads_browser_table'];
		var navigation = '<h2 class="dlm-reports-tab-navigation nav-tab-wrapper">';
		jQuery( this.container ).html( '' );
		jQuery( this.container ).append('<div class="">');

		Object.keys( $data ).forEach( key => {

			// the table
			var table = jQuery( document.createElement( 'table' ) );
			var table_class = 'hidden';
			var link_class = '';

			if ( 'desktop' == key ) {
				table_class = '';
				link_class = 'nav-tab-active';
			}

			navigation += '<a href="#' + key + '" class="nav-tab ' + link_class + '">' + key + '</a>';

			table.attr( 'cellspacing', 0 ).attr( 'cellpadding', 0 ).attr( 'border', 0 ).attr( 'id', key ).attr( 'class', table_class );

			// setup header row
			var headerRow = document.createElement( 'tr' );

			for ( var i = 0; i < $data[key][0].length; i++ ) {

				var th = document.createElement( 'th' );
				th.innerHTML = $data[key][0][i];
				headerRow.appendChild( th );
			}

			// append header row
			table.append( headerRow );

			for ( var i = 1; i < $data[key].length; i++ ) {
				// new row
				var tr = document.createElement( 'tr' );

				// loop
				for ( var j = 0; j < $data[key][i].length; j++ ) {
					var td = document.createElement( 'td' );
					td.innerHTML = $data[key][i][j];
					tr.appendChild( td );
				}
				// append row
				table.append( tr );
			}

			// put table in container
			jQuery( this.container ).append( table );
		} );

		navigation += '</div>';

		jQuery( this.container ).prepend( navigation );

	} else {

		// the table
		var table = jQuery( document.createElement( 'table' ) );

		table.attr( 'cellspacing', 0 ).attr( 'cellpadding', 0 ).attr( 'border', 0 );

		// setup header row
		var headerRow = document.createElement( 'tr' );

		for ( var i = 0; i < this.data[0].length; i++ ) {
			var th = document.createElement( 'th' );
			th.innerHTML = this.data[0][i];
			headerRow.appendChild( th );
		}

		// append header row
		table.append( headerRow );

		for ( var i = 1; i < this.data.length; i++ ) {
			// new row
			var tr = document.createElement( 'tr' );

			// loop
			for ( var j = 0; j < this.data[i].length; j++ ) {
				var td = document.createElement( 'td' );
				td.innerHTML = this.data[i][j];
				tr.appendChild( td );
			}

			// append row
			table.append( tr );
		}

		// put table in container
		jQuery( this.container ).html( '' ).append( table );
	}

};