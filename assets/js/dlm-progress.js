// This will hold the the file as a local object URL
let _OBJECT_URL;

function handleDownloadClick(e) {
	
	const button = this;
	const href = button.getAttribute('href');
	let triggerObject = {
		button: this,
		href: href,
		hiddenInfo:jQuery('data.dlm-hidden-info[data-url="'+ href +'"]'),
		buttonObj: jQuery(this)
	};

	// Return if this is a redirect or there is no data
	if ( 0 === triggerObject.hiddenInfo.length || 'download' !== triggerObject.hiddenInfo.data('action') ) {
		return;
	}
	
	// Show the progress bar after complete download also
	if ( triggerObject.href.indexOf( 'blob:http' ) !== -1 ) {

		triggerObject.buttonObj.addClass( 'download-100' );
		setTimeout(function(){
			triggerObject.buttonObj.removeClass( 'download-100' );
		},1500);
		
		return;
	}

	triggerObject.action = triggerObject.hiddenInfo.data('action');
	triggerObject.id = triggerObject.hiddenInfo.data('id');

	e.preventDefault();

	// Check if visitor has permission to download
	const permission_data = {
		action:'dlm_check_permission',
		download_id:triggerObject.id,
		_nonce: dlmProgressVar.nonce
	}

	jQuery.post(dlmProgressVar.ajaxUrl, permission_data, function (res) {
		if ( res.data.permission ) {
			// If the visitor has permission to download, we can start the download
			retrieveBlob(triggerObject);
		} else {
			// If the visitor does not have permission to download, we can show a message
			alert('no permissions');
		}
	});
	
}

// Loop through all .download-links buttons and add click event listener to them
document.querySelectorAll('.download-link,.download-button').forEach(
	function (button) {
		button.addEventListener('click', handleDownloadClick);
	},
	{ once: true }
);

function retrieveBlob( triggerObject ){
	const { button, href, hiddenInfo, buttonObj, id } = triggerObject;

	const request = new XMLHttpRequest();
	const buttonClass = buttonObj.attr('class');

	button.setAttribute('href', '#');
	button.removeAttribute('download');
	button.setAttribute('disabled', 'disabled');

	// Trigger the `dlm_download_triggered` action
	jQuery(document).trigger( 'dlm_download_triggered', [ this, button, hiddenInfo, _OBJECT_URL ] );

	request.responseType = 'blob';
	request.onreadystatechange = function () {
		let { status, readyState, statusText } = request;

		if (status == 404 && readyState == 2) {
			let p = document.createElement('p');
			p.innerHTML = statusText;
			button.parentNode.appendChild(p);
		}

		if (status == 401 && readyState == 2) {
			window.location.href = statusText;
		}

		if (status == 403 && readyState == 2) {
			let p = document.createElement('p');
			p.innerHTML = statusText;
			button.parentNode.appendChild(p);
		}

		if (request.status == 200 && request.readyState == 4) {
			let blob = request.response;
			_OBJECT_URL = URL.createObjectURL(blob);
			// Ajax request to update the log
			let data = {
				action: 'log_download',
				download_id: id,
				_nonce: dlmProgressVar.nonce
			};

			jQuery.post(dlmProgressVar.ajaxUrl, data, function (res) {

				// Remove event listener
				button.removeEventListener('click', handleDownloadClick);

				let filename = res.data;
				// Set the href of the a.download-complete to the object URL
				button.setAttribute('href', _OBJECT_URL);
				hiddenInfo.attr('data-url', _OBJECT_URL);
				// Set the download attribute of the a.download-complete to the file name
				button.setAttribute('download', `${filename}`);
				// Trigger click on a.download-complete
				button.click();
				setTimeout(function(){
					buttonObj.removeClass().addClass(buttonClass + ' dlm-download-complete');
				},600);
				// Trigger the `dlm_download_complete` action
				jQuery(document).trigger( 'dlm_download_downloaded', [ this, button, hiddenInfo, _OBJECT_URL ] );

				button.addEventListener('click', handleDownloadClick);
			});

			// Append the paragraph to the download-contaner
			hiddenInfo.find('span:not(.progress, .progress-inner)').remove();
			hiddenInfo.find('.progress, .progress .progress-inner').removeClass('dlm-visible-spinner');
			// Trigger the `dlm_download_complete` action
			jQuery(document).trigger( 'dlm_download_complete', [ this, button, hiddenInfo, _OBJECT_URL ] );
		}
	};

	request.addEventListener('progress', function (e) {
		let percent_complete = (e.loaded / e.total) * 100;
		// Force perfect complete to have 2 digits
		percent_complete = Math.round( percent_complete );
		let $class       = 'download-' + Math.ceil( percent_complete / 10) * 10;
	
		// Show spinner
		hiddenInfo.css('visibility','visible');
		buttonObj.removeClass().addClass(buttonClass + ' ' + $class);
		hiddenInfo.find('.progress, .progress .progress-inner').addClass('dlm-visible-spinner');
		// Trigger the `dlm_download_progress` action
		jQuery(document).trigger( 'dlm_download_progress', [ this, button, hiddenInfo, _OBJECT_URL, e, percent_complete ] );
	});

	request.onerror = function () {
		console.log('** An error occurred during the transaction');
	};
	request.open('GET', href, true);

	request.send();
}