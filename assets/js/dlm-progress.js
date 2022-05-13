// This will hold the the file as a local object URL
let _OBJECT_URL;

function handleDownloadClick(obj,e) {

	e.stopPropagation();
	const button = obj;
	const href = button.getAttribute('href');
	const hiddenInfo = jQuery('data.dlm-hidden-info[data-url="' + href + '"]');
	let triggerObject = {
		button: obj,
		href: href,
		hiddenInfo: hiddenInfo,
		buttonObj: jQuery(obj),
		redirect: hiddenInfo.data('redirect'),
	};

	// Return if this is a redirect or there is no data
	if (0 === triggerObject.hiddenInfo.length || 'download' !== triggerObject.hiddenInfo.data('action')) {
		return;
	}

	// Show the progress bar after complete download also
	if (triggerObject.href.indexOf('blob:http') !== -1) {

		triggerObject.buttonObj.addClass('download-100');
		setTimeout(function () {
			triggerObject.buttonObj.removeClass('download-100');
		}, 1500);

		return;
	}

	triggerObject.action = triggerObject.hiddenInfo.data('action');
	triggerObject.id = triggerObject.hiddenInfo.data('id');

	e.preventDefault();

	// Check if visitor has permission to download
	const permission_data = {
		action: 'dlm_check_permission',
		download_id: triggerObject.id,
		_nonce: dlmProgressVar.nonce
	}

	jQuery.post(dlmProgressVar.ajaxUrl, permission_data, function (res) {
		if (res.data.permission) {
			triggerObject.file_name = res.data.file_name;
			triggerObject.cookie_data = res.data.cookie_data;
			// If the visitor has permission to download, we can start the download
			retrieveBlob(triggerObject);
		} else {
			if ( res.data.no_access_url ) {
				window.location.href = res.data.no_access_url;
			} else {
				// If the visitor does not have permission to download, we can show a message
				triggerObject.buttonObj.after('<span class="dlm-permission-message"> - ' + dlmProgressVar.no_permissions + '</span>');
			}
		}
	});
}

// Create the classes that we will target
let xhr_links = '';
let $i = '';
jQuery.each( dlmProgressVar.xhr_links.class, function( $key, $value ){
	xhr_links += $i + ' .' + $value;
	$i = ',';
	
});

//
jQuery('html, body').one('click', xhr_links, function(e){
	handleDownloadClick(this,e);
});

function retrieveBlob(triggerObject) {
	const {
		button,
		href,
		hiddenInfo,
		buttonObj,
		id,
		file_name,
		cookie_data
	} = triggerObject;

	const request = new XMLHttpRequest();
	const buttonClass = buttonObj.attr('class');

	button.setAttribute('href', '#');
	button.removeAttribute('download');
	button.setAttribute('disabled', 'disabled');

	// Trigger the `dlm_download_triggered` action
	jQuery(document).trigger('dlm_download_triggered', [this, button, hiddenInfo, _OBJECT_URL]);

	request.responseType = 'blob';
	request.onreadystatechange = function () {
		let {
			status,
			readyState,
			statusText
		} = request;

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
				action: 'dlm_create_blob',
				download_id: id,
				_nonce: dlmProgressVar.nonce
			};

			// Remove event listener
			button.removeEventListener('click', handleDownloadClick);

			// Set the href of the a.download-complete to the object URL
			button.setAttribute('href', _OBJECT_URL);
			hiddenInfo.attr('data-url', _OBJECT_URL);
			// Set the download attribute of the a.download-complete to the file name
			button.setAttribute('download', `${file_name}`);
			// Trigger click on a.download-complete
			button.click();
			setTimeout(function () {
				buttonObj.removeClass().addClass(buttonClass + ' dlm-download-complete');
			}, 600);
			// Trigger the `dlm_download_complete` action
			jQuery(document).trigger('dlm_download_downloaded', [this, button, hiddenInfo, _OBJECT_URL]);

			button.addEventListener('click', handleDownloadClick);

			// Append the paragraph to the download-contaner
			hiddenInfo.find('span:not(.progress, .progress-inner)').remove();
			hiddenInfo.find('.progress, .progress .progress-inner').removeClass('dlm-visible-spinner');
			// Trigger the `dlm_download_complete` action
			jQuery(document).trigger('dlm_download_complete', [this, button, hiddenInfo, _OBJECT_URL]);
		}
	};

	request.addEventListener('progress', function (e) {
		let percent_complete = (e.loaded / e.total) * 100;
		// Force perfect complete to have 2 digits
		percent_complete = Math.round(percent_complete);
		let $class = 'download-' + Math.ceil(percent_complete / 10) * 10;

		// Show spinner
		hiddenInfo.css('visibility', 'visible');
		buttonObj.removeClass().addClass(buttonClass + ' ' + $class);
		hiddenInfo.find('.progress, .progress .progress-inner').addClass('dlm-visible-spinner');
		// Trigger the `dlm_download_progress` action
		jQuery(document).trigger('dlm_download_progress', [this, button, hiddenInfo, _OBJECT_URL, e, percent_complete]);
	});

	request.onerror = function () {
		console.log('** An error occurred during the transaction');
	};
	request.open('GET', href, true);

	// Set the cookie to remember the user has downloaded this file
	//Cookies.set(cookie_data.name,cookie_data.value,{ expires: cookie_data.expires,path: cookie_data.path,domain: cookie_data.domain, secure: cookie_data.secure });
	request.send();
}