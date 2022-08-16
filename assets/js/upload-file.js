jQuery(function ($) {

	/**
	 * Set up the Media Uploader
	 */

	let dlmUploadButtons = [],
		dlmUploader      = {};

	var uploadHandler = Backbone.Model.extend(
		{
			initialize: function ($args) {
				this.uploaderOptions = $args;

				var dlmUploaderInstance = this,
					uploader,
					dropzone;

				uploader = new wp.Uploader(dlmUploaderInstance.uploaderOptions);

				// Dropzone events
				dropzone = uploader.dropzone;
				dropzone.on('dropzone:enter', dlmUploaderInstance.show);
				dropzone.on('dropzone:leave', dlmUploaderInstance.hide);

				uploader.uploader.bind('FilesAdded', dlmUploaderInstance.dlmFileAdded);
				uploader.uploader.bind('FileUploaded', dlmUploaderInstance.dlmAddFileToPath);
				uploader.uploader.bind('Error', dlmUploaderInstance.dlmUploadError);


			},
			show      : function () {
				var $el = $('#dlm-uploader-container').show();

				// Ensure that the animation is triggered by waiting until
				// the transparent element is painted into the DOM.
				_.defer(function () {
					$el.css({opacity: 1});
				});
			},
			hide      : function () {
				var $el = $('#dlm-uploader-container').css({opacity: 0});

				wp.media.transition($el).done(function () {
					// Transition end events are subject to race conditions.
					// Make sure that the value is set as intended.
					if ('0' === $el.css('opacity')) {
						$el.hide();
					}
				});

				// https://core.trac.wordpress.org/ticket/27341
				_.delay(function () {
					if ('0' === $el.css('opacity') && $el.is(':visible')) {
						$el.hide();
					}
				}, 500);
			},
			/**
			 * Add the file url to File URLs meta
			 * @param {*} up
			 * @param {*} file
			 */
			dlmAddFileToPath: function (up, file) {

				const fileUrl  = file.attachment.attributes.url;
				const fileURLs = jQuery(up.settings.browse_button).parents('td').find('textarea');
				fileURLs.parent().removeClass('dlm-blury');
				let filePaths = fileURLs.val();
				filePaths     = filePaths ? filePaths + "\n" + fileUrl : fileUrl;
				fileURLs.val(filePaths);
			},
			/**
			 * Blur the textarea so the user knows it is loading
			 * @param {*} up
			 * @param {*} file
			 */
			dlmFileAdded: function (up, file) {

				const fileURLs = jQuery(up.settings.browse_button).parents('td').find('textarea');
				fileURLs.parent().addClass('dlm-blury');
			},
			/**
			 * Blur the textarea so the user knows it is loading
			 * @param {*} up
			 * @param {*} pluploadError
			 */
			dlmUploadError: function (up, pluploadError) {
				jQuery(up.settings.browse_button).parent().append('<p class="error description" style="color:red;">' + pluploadError.message + '</p>');
				setTimeout(function () {
					jQuery(up.settings.browse_button).parent().find('.error.description').remove();
				}, 3500);
			}
		}
	);

	dlmUploader['uploadHandler'] = uploadHandler;

	$('.dlm_upload_file').each((index, element) => {

		dlmUploadButtons.push($(element));

		const dlmUploaderOptions = {
				  browser  : $(element),
				  plupload : {
					  multi_selection: false,
				  },
				  params   : {
					  type: 'dlm_download'
				  },
				  container: $(element).parents('table.dlm-metabox-content'),
				  dropzone : $(element).parents('table.dlm-metabox-content'),
			  },
			  dlmUploadeFile     = new dlmUploader['uploadHandler'](dlmUploaderOptions);

	});

	$(document).on('dlm_new_file_added', () => {

		$('.dlm_upload_file').each((index, element) => {

			if (dlmUploadButtons.includes($(element))) {
				return true;
			}

			dlmUploadButtons.push($(element));

			const dlmUploaderOptions = {
					  browser  : $(element),
					  plupload : {
						  multi_selection: false,
					  },
					  params   : {
						  type: 'dlm_download'
					  },
					  container: $(element).parents('table.dlm-metabox-content'),
					  dropzone : $(element).parents('table.dlm-metabox-content'),
				  },
				  dlmUploaderFile    = new wp.Uploader(dlmUploaderOptions);
		});

	});
});