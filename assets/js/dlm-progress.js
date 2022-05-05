// This will hold the the file as a local object URL
let _OBJECT_URL;

function handleDownloadClick(e) {
	let href = this.getAttribute('href');
	const hiddenInfo = jQuery('data.dlm-hidden-info[data-url="'+ href +'"]');

	if ( 0 === hiddenInfo.length ) {		
		p.innerHTML = dlmProgressVar.no_info + '<span>&#x2713;</span>';
		this.parentNode.appendChild(p);
		return;
	}

	const action = hiddenInfo.data('action');
	const id = hiddenInfo.data('id');
	const slug = hiddenInfo.data('slug');

	// If this is a redirect we should bail
	if ( 'download' !== action ) {
		return;
	}

	e.preventDefault();

	const request = new XMLHttpRequest();
	// Bind this
	const button      = this;
	const buttonObj   = jQuery( button );
	const buttonClass = buttonObj.attr('class');
	let text = button.innerHTML;

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
			};
			jQuery.post(dlmProgressVar.ajaxUrl, data, function (res) {
				let filename = res.data;
				// Set the href of the a.download-complete to the object URL
				button.setAttribute('href', _OBJECT_URL);
				// Set the download attribute of the a.download-complete to the file name
				button.setAttribute('download', `${filename}`);
				// Trigger click on a.download-complete
				button.click();
			});

			// Append the paragraph to the download-contaner
			hiddenInfo.find('span:not(.progress, .progress-inner)').remove();
			hiddenInfo.find('.progress, .progress .progress-inner').removeClass('dlm-visible-spinner');
			setTimeout(function(){
				buttonObj.removeClass().addClass(buttonClass);
			},600);
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
	// Remove event listener
	this.removeEventListener('click', handleDownloadClick);
}

// Loop through all .download-links buttons and add click event listener to them
document.querySelectorAll('.download-link,.download-button').forEach(
	function (button) {
		button.addEventListener('click', handleDownloadClick);
	},
	{ once: true }
);
