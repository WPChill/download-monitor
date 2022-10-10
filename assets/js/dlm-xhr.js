jQuery(function ($) {
	// Let's initiate the reports.
	new DLM_XHR_Download();
});


class DLM_XHR_Download {

	constructor() {
		// dlmXHRinstance defined in inline script
		dlmXHRinstance = this;
		this.init();
	}

	init() {
		dlmXHRinstance.attachButtonEvent();
	}

	// Function to attach events for the download buttons
	attachButtonEvent() {

		// Create the classes that we will target
		let xhr_links = '';
		let $i        = '';

		jQuery.each(dlmXHR.xhr_links.class, function ($key, $value) {
			if ($value.indexOf('[class') > -1 || $value.indexOf('[id') > -1) {
				xhr_links += $i + ' ' + $value;
			} else {
				xhr_links += $i + ' .' + $value;
			}

			$i = ',';
		});

		jQuery('html, body').on('click', xhr_links, function (e) {
			if (jQuery(this).hasClass('dlm-no-xhr-download')) {
				return true;
			}
			dlmXHRinstance.handleDownloadClick(this, e);
		});
	}

	handleDownloadClick(obj, e) {
		e.stopPropagation();
		const href = obj.getAttribute('href');

		let triggerObject = {
			button   : obj,
			href     : href,
			buttonObj: jQuery(obj),
		};

		// Show the progress bar after complete download also
		if (triggerObject.href.indexOf('blob:http') !== -1) {

			/*triggerObject.buttonObj.addClass('download-100');
			setTimeout(function () {
				triggerObject.buttonObj.removeClass('download-100');
			}, 1500);*/

			return;
		}

		if ('#' === triggerObject.href) {
			return;
		}

		e.preventDefault();
		dlmXHRinstance.retrieveBlob(triggerObject);
	}

	retrieveBlob(triggerObject) {
		let {
				button,
				href,
				buttonObj,
			} = triggerObject;

		// This will hold the file as a local object URL
		let _OBJECT_URL;
		const request      = new XMLHttpRequest(),
			  $setCookie   = dlmXHR.prevent_duplicates,
			  buttonTarget = buttonObj.attr('target');
		let buttonClass  = buttonObj.attr('class');
		buttonClass = ('undefined' !== typeof buttonClass && '' !== buttonClass ) ? buttonClass.replace('dlm-download-started', '').replace('dlm-download-completed','') : '';

		buttonObj.addClass('dlm-download-started');
		button.setAttribute('href', '#');
		button.removeAttribute('download');
		button.setAttribute('disabled', 'disabled');

		const newHref = (href.indexOf('?') > 0) ? href + '&nonce=' + dlmXHR.nonce : href + '?nonce=' + dlmXHR.nonce;

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

			if ('undefined' !== typeof responseHeaders['dlm-no-waypoints']) {
				request.abort();
				window.location.href = href;
			}

			if ('undefined' !== typeof responseHeaders['dlm-external-download']) {
				request.abort();
				let file_name = responseHeaders['dlm-file-name'].replace(/\"/g, '').replace(';', '');
				dlmXHRinstance.dlmExternalDownload(responseHeaders, button, buttonObj, file_name, href);
				return;
			}

			if (request.readyState == 2 && 'undefined' !== typeof responseHeaders['dlm-error'] && '' !== responseHeaders['dlm-error'] && null !== responseHeaders['dlm-error']) {

				dlmXHRinstance.dlmLogDownload(responseHeaders, 'failed', false);
				button.removeAttribute('download');
				button.setAttribute('href', href);
				buttonObj.removeClass().addClass(buttonClass).find('span.dlm-xhr-progress').remove();
				request.abort();
				buttonObj.append('<span class="dlm-xhr-error">' + responseHeaders['dlm-error'] + '</span>');
				return;
			}

			if (request.readyState == 2 && 'undefined' !== typeof responseHeaders['dlm-redirect'] && '' !== responseHeaders['dlm-redirect'] && null !== responseHeaders['dlm-redirect']) {
				dlmXHRinstance.dlmLogDownload(responseHeaders, 'redirected', false, responseHeaders['dlm-redirect'], responseHeaders['dlm-no-access'], buttonTarget);
				button.removeAttribute('download');
				button.setAttribute('href', href);
				buttonObj.removeClass().addClass(buttonClass).find('span.dlm-xhr-progress').remove();
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
				file_name = file_name.replace(/\"/g, '').replace(';', '');

				_OBJECT_URL = URL.createObjectURL(blob);
				// Remove event listener
				button.removeEventListener('click', dlmXHRinstance.handleDownloadClick);
				// Set the download attribute of the a.download-complete to the file name
				button.setAttribute('download', `${file_name}`);
				// Set the href of the a.download-complete to the object URL
				button.setAttribute('href', _OBJECT_URL);
				// Trigger click on a.download-complete
				button.click();
				buttonObj.removeClass().addClass(buttonClass + ' dlm-download-complete');

				dlmXHRinstance.attachButtonEvent();

				// Append the paragraph to the download-contaner
				// Trigger the `dlm_download_complete` action
				jQuery(document).trigger('dlm_download_complete', [this, button, buttonObj, _OBJECT_URL]);
				dlmXHRinstance.dlmLogDownload(responseHeaders, 'completed', $setCookie);
				// Recommended : Revoke the object URL after some time to free up resources
				window.URL.revokeObjectURL(_OBJECT_URL);
				button.removeAttribute('download');
				button.setAttribute('href', href);

				// There is no way to find out whether user finished downloading
				setTimeout(function () {
					buttonObj.removeClass().addClass(buttonClass).find('span.dlm-xhr-progress').remove();
				}, 4000);
			}
		};

		request.addEventListener('progress', function (e) {
			let percent_complete = (e.loaded / e.total) * 100;
			// Force perfect complete to have 2 digits
			percent_complete     = percent_complete.toFixed(2);
			let $class           = 'dlm-download-started';
			buttonObj.find('span.dlm-xhr-progress').remove();
			// Comment below lines for the new XHR loader so that we know where to rever
			$class = $class + ' download-' + Math.ceil(percent_complete / 10) * 10;

			if (Infinity != percent_complete) {
				buttonObj.append('<span class="dlm-xhr-progress">&nbsp;' + percent_complete + '%</span>');
			}

			// Show spinner
			buttonObj.removeClass().addClass(buttonClass + ' ' + $class);
			// Trigger the `dlm_download_progress` action
			jQuery(document).trigger('dlm_download_progress', [this, button, buttonObj, _OBJECT_URL, e, percent_complete]);
		});

		request.onerror = function () {
			button.removeAttribute('download');
			button.setAttribute('href', href);
			buttonObj.removeClass().addClass(buttonClass + ' dlm-no-xhr-download').find('span.dlm-xhr-progress').remove();
			buttonObj.append('<span class="dlm-xhr-error">' + dlmXHRtranslations.error + '</span>');
			console.log('** An error occurred during the transaction');
		};

		request.open('GET', newHref, true);
		request.setRequestHeader('dlm-xhr-request', 'dlm_XMLHttpRequest');
		request.send();
	}

	dlmLogDownload(headers, status, cookie, redirect_path = null, no_access = null, target = '_self') {

		if (null !== no_access) {
			window.location.href = redirect_path;
			return;
		}

		const currentURL  = window.location.href;
		const download_id = headers['dlm-download-id'];
		const version_id  = headers['dlm-version-id'];
		const data = {
			download_id,
			version_id,
			status,
			cookie,
			currentURL,
			action: 'log_dlm_xhr_download',
			responseHeaders : headers,
			nonce : dlmXHR.nonce
		};

		jQuery.post(dlmXHR.ajaxUrl, data, function (response) {
			if (null !== redirect_path) {
				// If the link has no target attribute, then open in the same window
				if (null == target) {
					target = '_self';
				}
				window.open(redirect_path, target);
			}
		});
	}

	dlmExternalDownload(headers, button, buttonObj, file_name, href) {
		const request      = new XMLHttpRequest(),
			  uri          = headers['dlm-external-download'],
			  buttonTarget = buttonObj.attr('target');
		let buttonClass = buttonObj.attr('class'),
			_OBJECT_URL;
		buttonClass = ('undefined' !== typeof buttonClass && '' !== buttonClass ) ? buttonClass.replace('dlm-download-started', '').replace('dlm-download-completed','') : '';

		buttonObj.addClass('dlm-download-started');
		button.setAttribute('href', '#');
		button.removeAttribute('download');
		button.setAttribute('disabled', 'disabled');

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

			if (403 === status) {
				dlmXHRinstance.dlmLogDownload(responseHeaders, 'failed', false);
				request.abort();
				buttonObj.append('<span class="dlm-xhr-error">Acces Denied to file.</span>');
				return;
			}
			if (status == 200 && readyState == 4) {

				let blob = request.response;

				_OBJECT_URL = URL.createObjectURL(blob);
				// Remove event listener
				button.removeEventListener('click', dlmXHRinstance.handleDownloadClick);
				// Set the download attribute of the a.download-complete to the file name
				button.setAttribute('download', `${file_name}`);
				// Set the href of the a.download-complete to the object URL
				button.setAttribute('href', _OBJECT_URL);
				// Trigger click on a.download-complete
				button.click();
				buttonObj.removeClass().addClass(buttonClass + ' dlm-download-complete');

				dlmXHRinstance.attachButtonEvent();

				// Append the paragraph to the download-contaner
				// Trigger the `dlm_download_complete` action
				jQuery(document).trigger('dlm_download_complete', [this, button, buttonObj, _OBJECT_URL]);
				dlmXHRinstance.dlmLogDownload(responseHeaders, 'completed', false);
				// Recommended : Revoke the object URL after some time to free up resources
				window.URL.revokeObjectURL(_OBJECT_URL);
				button.removeAttribute('download');
				button.setAttribute('href', href);
				// There is no way to find out whether user finished downloading
				setTimeout(function () {
					buttonObj.removeClass().addClass(buttonClass).find('span.dlm-xhr-progress').remove();
				}, 1000);
			}
		};

		request.addEventListener('progress', function (e) {
			let percent_complete = (e.loaded / e.total) * 100;
			// Force perfect complete to have 2 digits
			percent_complete     = percent_complete.toFixed(2);
			let $class           = 'dlm-download-started';
			buttonObj.find('span.dlm-xhr-progress').remove();
			// Comment below lines for the new XHR loader so that we know where to rever
			$class = $class + ' download-' + Math.ceil(percent_complete / 10) * 10;

			if (Infinity != percent_complete) {
				buttonObj.append('<span class="dlm-xhr-progress">&nbsp;' + percent_complete + '%</span>');
			}

			// Show spinner
			buttonObj.removeClass().addClass(buttonClass + ' ' + $class);
			// Trigger the `dlm_download_progress` action
			jQuery(document).trigger('dlm_download_progress', [this, button, buttonObj, _OBJECT_URL, e, percent_complete]);
		});

		request.onerror = function () {
			button.removeAttribute('download');
			button.setAttribute('href', href);
			buttonObj.removeClass().addClass(buttonClass + ' .dlm-no-xhr-download').find('span.dlm-xhr-progress').remove();
			buttonObj.append('<span class="dlm-xhr-error">' + dlmXHRtranslations.error + '</span>');
			console.log('** An error occurred during the transaction');
		};

		request.open('GET', uri, true);
		request.setRequestHeader('dlm-xhr-request', 'dlm_XMLHttpRequest');
		request.send();
	}
}
