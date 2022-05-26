// Function to attach events for the download buttons
function attachButtonEvent() {

	// Create the classes that we will target
	let xhr_links = '';
	let $i = '';
	jQuery.each(dlmProgressVar.xhr_links.class, function ($key, $value) {
		xhr_links += $i + ' .' + $value;
		$i = ',';

	});

	//
	jQuery('html, body').on('click', xhr_links, function (e) {
		handleDownloadClick(this, e);
	});
}

// Attach the first event
attachButtonEvent();

function handleDownloadClick(obj, e) {

	e.stopPropagation();
	const button = obj;
	const href = button.getAttribute('href');

	let triggerObject = {
		button: obj,
		href: href,
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

	const request = new XMLHttpRequest(),
		buttonClass = buttonObj.attr('class');
	button.setAttribute('href', '#');
	button.removeAttribute('download');
	button.setAttribute('disabled', 'disabled');

	href = (href.indexOf('/?') > 0) ? href + '&nonce=' + dlmProgressVar.nonce : href + '?nonce=' + dlmProgressVar.nonce;

	// Trigger the `dlm_download_triggered` action
	jQuery(document).trigger('dlm_download_triggered', [this, button, _OBJECT_URL]);

	request.responseType = 'blob';
	request.onreadystatechange = function () {
		let {
			status,
			readyState,
			statusText
		} = request;

		let headers = request
			.getAllResponseHeaders()
			.split('\r\n')
			.reduce((result, current) => {
				let [name, value] = current.split(': ');
				result[name] = value;
				return result;
			}, {});

		if ('undefined' !== typeof headers['dlm-error'] && '' !== headers['dlm-error'] && null !== headers['dlm-error']) {
			alert(eaders['dlm-error']);
			return;
		}

		if ('undefined' !== typeof headers['dlm-redirect'] && '' !== headers['dlm-redirect'] && null !== headers['dlm-redirect']) {
			window.location.href = headers['dlm-redirect'];
			return;
		}

		if (request.readyState == 2 && request.status == 200) {
			// Download is being started
			//button.parent().append('<span>Download started</span>');
		}

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

		if (status == 200 && readyState == 4) {

			let blob = request.response;
			let file_name = request.getResponseHeader('Content-Disposition').split('filename=')[1];
			file_name = file_name.replace(/\"/g, '').replace(';', '');

			_OBJECT_URL = URL.createObjectURL(blob);

			// Remove event listener
			button.removeEventListener('click', handleDownloadClick);

			// Set the href of the a.download-complete to the object URL
			button.setAttribute('href', _OBJECT_URL);
			// Set the download attribute of the a.download-complete to the file name
			button.setAttribute('download', `${file_name}`);
			// Trigger click on a.download-complete
			button.click();
			setTimeout(function () {
				buttonObj.removeClass().addClass(buttonClass + ' dlm-download-complete');
			}, 600);
			// Trigger the `dlm_download_complete` action
			jQuery(document).trigger('dlm_download_downloaded', [this, button, _OBJECT_URL]);

			attachButtonEvent();

			// Append the paragraph to the download-contaner
			// Trigger the `dlm_download_complete` action
			jQuery(document).trigger('dlm_download_complete', [this, button, _OBJECT_URL]);

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
		percent_complete = Math.round(percent_complete);
		let $class = 'download-' + Math.ceil(percent_complete / 10) * 10;

		// Show spinner
		buttonObj.removeClass().addClass(buttonClass + ' ' + $class);
		// Trigger the `dlm_download_progress` action
		jQuery(document).trigger('dlm_download_progress', [this, button, _OBJECT_URL, e, percent_complete]);
	});

	request.onerror = function () {
		console.log('** An error occurred during the transaction');
	};

	request.open('GET', href, true);
	request.setRequestHeader('dlm-xhr-request', 'dlm_XMLHttpRequest');
	request.send();
}