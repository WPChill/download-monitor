// Function to attach events for the download buttons
function attachButtonEvent() {

	// Create the classes that we will target
	let xhr_links = '';
	let $i        = '';
	jQuery.each(dlmXHR.xhr_links.class, function ($key, $value) {
		if ($value.indexOf('[class=') || $value.indexOf('[id=')) {
			xhr_links += $i + ' ' + $value;
		} else {
			xhr_links += $i + ' .' + $value;
		}

		$i = ',';
	});

	jQuery('html, body').on('click', xhr_links, function (e) {
		handleDownloadClick(this, e);
	});
}

// Attach the first event
attachButtonEvent();

function handleDownloadClick(obj, e) {

	e.stopPropagation();
	const href   = obj.getAttribute('href');

	let triggerObject = {
		button   : obj,
		href     : href,
		buttonObj: jQuery(obj),
	};

	// Show the progress bar after complete download also
	if (triggerObject.href.indexOf('blob:http') !== -1) {

		triggerObject.buttonObj.addClass('download-100');
		setTimeout(function () {
			triggerObject.buttonObj.removeClass('download-100');
		}, 1500);

		return;
	}

	if ('#' === triggerObject.href) {
		console.log('No file path found');
		return;
	}

	e.preventDefault();
	retrieveBlob(triggerObject);
}

function retrieveBlob(triggerObject) {
	let {
			button,
			href,
			buttonObj,
		} = triggerObject;

	// This will hold the the file as a local object URL
	let _OBJECT_URL;
	const request     = new XMLHttpRequest(),
		  buttonClass = buttonObj.attr('class');

	buttonObj.addClass('dlm-download-started');
	button.setAttribute('href', '#');
	button.removeAttribute('download');
	button.setAttribute('disabled', 'disabled');

	href = (href.indexOf('/?') > 0) ? href + '&nonce=' + dlmXHR.nonce : href + '?nonce=' + dlmXHR.nonce;

	// Trigger the `dlm_download_triggered` action
	jQuery(document).trigger('dlm_download_triggered', [this, button, buttonObj, _OBJECT_URL]);

	request.responseType       = 'blob';
	request.onreadystatechange = function () {
		let {
				status,
				readyState,
				statusText
			} = request;

		let responseHeaders = request
			.getAllResponseHeaders()
			.split('\r\n')
			.reduce((result, current) => {
				let [name, value] = current.split(': ');
				result[name]      = value;
				return result;
			}, {});

		if ('undefined' !== typeof responseHeaders['dlm-external-download']) {
			request.abort();
			let file_name = responseHeaders['dlm-file-name'].replace(/\"/g, '').replace(';', '');
			dlmExternalDownload(responseHeaders, button, buttonObj, file_name);
			return;
		}

		if (request.readyState == 2 && 'undefined' !== typeof responseHeaders['dlm-error'] && '' !== responseHeaders['dlm-error'] && null !== responseHeaders['dlm-error']) {

			dlmLogDonwload(responseHeaders['dlm-download-id'], responseHeaders['dlm-version-id'], 'failed', false);
			request.abort();
			buttonObj.append('<span class="dlm-xhr-error">' + responseHeaders['dlm-error'] + '</span>');
			return;
		}

		if (request.readyState == 2 && 'undefined' !== typeof responseHeaders['dlm-redirect'] && '' !== responseHeaders['dlm-redirect'] && null !== responseHeaders['dlm-redirect']) {

			dlmLogDonwload(responseHeaders['dlm-download-id'], responseHeaders['dlm-version-id'], 'redirected', false, responseHeaders['dlm-redirect']);
			request.abort();
			return;
		}

		if (request.readyState == 2 && request.status == 200) {
			// Download is being started
			//button.parent().append('<span>Download started</span>');
		}

		if (status == 404 && readyState == 2) {
			let p       = document.createElement('p');
			p.innerHTML = statusText;
			button.parentNode.appendChild(p);
		}

		if (status == 401 && readyState == 2) {
			window.location.href = statusText;
		}

		if (status == 403 && readyState == 2) {
			let p       = document.createElement('p');
			p.innerHTML = statusText;
			button.parentNode.appendChild(p);
		}

		if (status == 200 && readyState == 4) {

			let blob      = request.response;
			let file_name = responseHeaders['content-disposition'].split('filename=')[1];
			file_name.replace(/\"/g, '').replace(';', '');

			_OBJECT_URL = URL.createObjectURL(blob);
			// Remove event listener
			button.removeEventListener('click', handleDownloadClick);
			// Set the download attribute of the a.download-complete to the file name
			button.setAttribute('download', `${file_name}`);
			// Set the href of the a.download-complete to the object URL
			button.setAttribute('href', _OBJECT_URL);
			// Trigger click on a.download-complete
			button.click();
			buttonObj.removeClass().addClass(buttonClass + ' dlm-download-complete');

			attachButtonEvent();

			// Append the paragraph to the download-contaner
			// Trigger the `dlm_download_complete` action
			jQuery(document).trigger('dlm_download_complete', [this, button, buttonObj, _OBJECT_URL]);
			dlmLogDonwload(responseHeaders['dlm-download-id'], responseHeaders['dlm-version-id'], 'completed', false);
			// Recommended : Revoke the object URL after some time to free up resources
			// There is no way to find out whether user finished downloading
			setTimeout(function () {
				window.URL.revokeObjectURL(_OBJECT_URL);
			}, 60 * 1000);
		}
	};

	request.addEventListener('progress', function (e) {
		let percent_complete = (e.loaded / e.total) * 100;
		// Force perfect complete to have 2 digits
		percent_complete     = Math.round(percent_complete);
		let $class           = 'download-' + Math.ceil(percent_complete / 10) * 10;

		if ('100' !== percent_complete) {
			$class = $class + ' dlm-download-started';
		}
		// Show spinner
		buttonObj.removeClass().addClass(buttonClass + ' ' + $class);
		// Trigger the `dlm_download_progress` action
		jQuery(document).trigger('dlm_download_progress', [this, button, buttonObj, _OBJECT_URL, e, percent_complete]);
	});

	request.onerror = function () {
		console.log('** An error occurred during the transaction');
	};

	request.open('GET', href, true);
	request.setRequestHeader('dlm-xhr-request', 'dlm_XMLHttpRequest');
	request.send();
}

function dlmLogDonwload(download_id, version_id, status, cookie, redirect_path = null) {
	const data = {
		download_id,
		version_id,
		status,
		cookie,
		action: 'log_dlm_xhr_download',
		nonce : dlmXHR.nonce
	};

	jQuery.post(dlmXHR.ajaxUrl, data, function (response) {
		if (null !== redirect_path) {
			window.location.href = redirect_path;
		}
	});
}

function dlmExternalDownload(headers, button, buttonObj, file_name) {
	const request     = new XMLHttpRequest(),
		  uri = headers['dlm-external-download'],
		  buttonClass = buttonObj.attr('class');
	let _OBJECT_URL;

	buttonObj.addClass('dlm-download-started');
	button.setAttribute('href', '#');
	button.removeAttribute('download');
	button.setAttribute('disabled', 'disabled');

	let href = (uri.indexOf('/?') > 0) ? uri + '&nonce=' + dlmXHR.nonce : uri + '?nonce=' + dlmXHR.nonce;

	// Trigger the `dlm_download_triggered` action
	jQuery(document).trigger('dlm_download_triggered', [this, button, buttonObj, _OBJECT_URL]);

	request.responseType       = 'blob';
	request.onreadystatechange = function () {
		let {
				status,
				readyState,
				statusText
			} = request;

		let responseHeaders = request
			.getAllResponseHeaders()
			.split('\r\n')
			.reduce((result, current) => {
				let [name, value] = current.split(': ');
				result[name]      = value;
				return result;
			}, {});

		if ( 403 === status ) {
			dlmLogDonwload(headers['dlm-download-id'], headers['dlm-version-id'], 'failed', false);
			request.abort();
			buttonObj.append('<span class="dlm-xhr-error">Acces Denied to file.</span>');
			return;
		}

		if (status == 200 && readyState == 4) {

			let blob      = request.response;

			_OBJECT_URL = URL.createObjectURL(blob);
			// Remove event listener
			button.removeEventListener('click', handleDownloadClick);
			// Set the download attribute of the a.download-complete to the file name
			button.setAttribute('download', `${file_name}`);
			// Set the href of the a.download-complete to the object URL
			button.setAttribute('href', _OBJECT_URL);
			// Trigger click on a.download-complete
			button.click();
			buttonObj.removeClass().addClass(buttonClass + ' dlm-download-complete');

			attachButtonEvent();

			// Append the paragraph to the download-contaner
			// Trigger the `dlm_download_complete` action
			jQuery(document).trigger('dlm_download_complete', [this, button, buttonObj, _OBJECT_URL]);
			dlmLogDonwload(headers['dlm-download-id'], headers['dlm-version-id'], 'completed', false);
			// Recommended : Revoke the object URL after some time to free up resources
			// There is no way to find out whether user finished downloading
			setTimeout(function () {
				window.URL.revokeObjectURL(_OBJECT_URL);
			}, 60 * 1000);
		}
	};

	request.onerror = function () {
		console.log('** An error occurred during the transaction');
	};

	request.open('GET', uri, true);
	request.setRequestHeader('dlm-xhr-request', 'dlm_XMLHttpRequest');
	request.send();
}