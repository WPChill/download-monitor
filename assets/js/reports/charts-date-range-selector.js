jQuery.fn.extend( {
	dlm_reports_date_range: function ( start_date, end_date, url ) {
		new DLM_Reports_Date_Range_Selector( this, start_date, end_date, url );
		return this;
	}
} );

var DLM_Reports_Date_Range_Selector = function ( c, sd, ed, u ) {

	this.container = c;
	this.startDate = new Date( sd );
	this.endDate = new Date( ed );
	this.url = u;
	this.el = null;
	this.opened = false;

	this.startDateInput = null;
	this.endDateInput = null;

	this.setup = function () {
		var instance = this;
		this.container.click( function () {
			instance.toggleDisplay();
			return false;
		} );
	};

	this.setup();

};

DLM_Reports_Date_Range_Selector.prototype.toggleDisplay = function () {
	if ( this.opened ) {
		this.hide();
	} else {
		this.display();
	}
};

DLM_Reports_Date_Range_Selector.prototype.display = function () {
	if ( this.opened ) {
		return;
	}
	this.opened = true;
	this.el = this.createElement();
	this.container.append( this.el );
	let element = this.el;

	var configObject = {
		separator: ' to ',
		getValue: function () {
			if ( element.find( '#dlm_start_date' ).val() && element.find( '#dlm_end_date' ).val() ) {
			console.log(element);
				element.append('<p>captain</p>');
			} else {

				return '';
			}

		},
		setValue : function ( s, s1, s2 ) {
			element.find( '#dlm_start_date' ).val( s1 );
			element.find( '#dlm_end_date' ).val( s2 );
		},
		inline: true,
		alwaysOpen: true,
		container:'#dlm_date_range_picker'
	};

	element.dateRangePicker( configObject );
};

DLM_Reports_Date_Range_Selector.prototype.hide = function () {
	this.opened = false;
	this.el.remove();
};

DLM_Reports_Date_Range_Selector.prototype.apply = function () {

	var sd = new Date( this.startDateInput.val() + "T00:00:00" );
	var ed = new Date( this.endDateInput.val() + "T00:00:00" );
	var sds = sd.getFullYear()+ "-"+(sd.getMonth()+1)+"-"+sd.getDate();
	var eds = ed.getFullYear()+ "-"+(ed.getMonth()+1)+"-"+ed.getDate();
	this.hide();
	window.location.replace( this.url + "&date_from=" + sds + "&date_to=" + eds );
};

DLM_Reports_Date_Range_Selector.prototype.createElement = function () {
	var instance = this;
	var el = jQuery( '<div>' ).addClass( 'dlm_rdrs_overlay' );
	var startDate = jQuery( '<div>' ).attr( 'id', 'dlm_date_range_picker' );
	this.startDateInput = jQuery( '<input>' ).attr( 'type', 'hidden' ).attr('id','dlm_start_date');
	this.endDateInput = jQuery( '<input>' ).attr( 'type', 'hidden' ).attr('id','dlm_end_date');
	var actions = jQuery( '<div>' ).addClass( 'dlm_rdrs_actions' );
	var applyButton = jQuery( '<a>' ).addClass( 'button' ).html( 'Apply' ).click( function () {
		instance.apply();
		return false;
	} );
	var ul = jQuery( '<ul>' ).addClass( 'date-preset-list' );
	var li = jQuery( '<li>' ).html( 'Yesterday' ).attr( 'date-range', 'yesterday' );
	var li2 = jQuery( '<li>' ).html( 'Last 7 Days' ).attr( 'date-range', 'last 7 days' );
	var li3 = jQuery( '<li>' ).html( 'Last 30 Days' ).attr( 'date-range', 'last 30 days' );
	var li4 = jQuery( '<li>' ).html( 'This Month' ).attr( 'date-range', 'this month' );
	var li5 = jQuery( '<li>' ).html( 'Last Month' ).attr( 'date-range', 'last month' );
	var li6 = jQuery( '<li>' ).html( 'This year' ).attr( 'date-range', 'this year' );
	var li7 = jQuery( '<li>' ).html( 'All Time' ).attr( 'date-range', 'all time' );

	actions.append( applyButton );
	ul.append(li).append(li2).append(li3).append(li4).append(li5).append(li6).append(li7);
	//el.append(ul).append( startDate ).append( endDate ).append( actions ).append( this.startDateInput ).append( this.endDateInput );
	// Don't append actions for now, for the purpose of the styling. Actions will be completly removed when going to React
	el.append(ul).append( startDate ).append( this.startDateInput ).append( this.endDateInput );

	el.click( function () {
		return false;
	} );
	return el;
};