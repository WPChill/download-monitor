// This will hold the the file as a local object URL
let _OBJECT_URL;

function handleDownloadLinkClick(e) {
	let href = this.getAttribute('href');

	// Regex to get the id
	let id = this.parentNode.className.match(/[0-9]/g).join('');

	e.preventDefault();

	let request = new XMLHttpRequest();
	// Bind this
	let button = this;
	button.setAttribute('href', '#');
	button.removeAttribute('download');
	button.setAttribute('disabled', 'disabled');

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
			button.style.display = 'none';
			document.querySelector(
				`.download-container-${id} .spinner`
			).style.display = 'none';

			// Append a paragraph to display message
			let p = document.createElement('p');
			p.innerHTML = 'Download complete <span>&#x2713;</span>';
			// Append the paragraph to the download-contaner
			document.querySelector(`.download-container-${id}`).appendChild(p);
		}
	};
	request.addEventListener('progress', function (e) {
		// Show spinner
		document.querySelector(
			`.download-container-${id} .spinner`
		).style.display = 'block';
	});
	request.onerror = function () {
		console.log('** An error occurred during the transaction');
	};
	request.open('GET', href, true);

	request.send();
	// Remove event listener
	this.removeEventListener('click', handleDownloadLinkClick);
}

function handleDownloadButtonClick(e) {
	let href = this.getAttribute('href');
	let id = this.className.match(/[0-9]/g).join('');
	this.setAttribute('href', '#');
	this.removeAttribute('download');
	this.setAttribute('disabled', 'disabled');
	e.preventDefault();

	// Bind this
	let button = this;

	let request = new XMLHttpRequest();
	request.open('GET', href, true);

	request.responseType = 'blob';
	request.onreadystatechange = function () {
		let { status, readyState, statusText, response } = request;

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
			let { status, readyState, statusText, response } = request;
			let blob = response;
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
				button.setAttribute('download', filename);
				// Trigger click on a.download-complete
				button.click();
			});

			setTimeout(() => {
				document.querySelector(
					`.download-button-${id} .progress .progress-inner`
				).style.display = 'none';
				let span = document.createElement('span');
				span.classList.add('download-done');
				// Append the span to the button child p;
				button.children[0].appendChild(span);
			}, 1000);
		}
	};
	request.addEventListener('progress', function (e) {
		let percent_complete = (e.loaded / e.total) * 100;
		// Force perfect complete to have 2 digits
		percent_complete = Math.round(percent_complete * 100) / 100;
		document.querySelector(
			`.download-button-${id} .progress .progress-inner`
		).style.width = `${percent_complete}%`;
	});

	request.onerror = function () {
		console.log('** An error occurred during the transaction');
	};

	request.send();
	// Remove event listener
	this.removeEventListener('click', handleDownloadButtonClick);
}
// Loop through all .download-links buttons and add click event listener to them
document.querySelectorAll('.download-link').forEach(
	function (button) {
		button.addEventListener('click', handleDownloadLinkClick);
	},
	{ once: true }
);

document.querySelectorAll('.download-button').forEach(
	function (button) {
		button.addEventListener('click', handleDownloadButtonClick);
	},
	{ once: true }
);
