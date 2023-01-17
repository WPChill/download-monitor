jQuery(function ($) {

	/**
	 * Set up the Media Uploader
	 */

	var dlmUploadButtons = [],
		dlmUploader      = {};

	class DLM_Edit_Download {

		constructor() {
			// dlmEditInstance declared in inline script.
			dlmEditInstance = this;
			this.init();
		}

		/**
		 * Init our functionality
		 */
		init() {
			this.createUploaders();
			this.initUploaders();
			this.newFileAction();
			this.removeFileAction();
			this.clickActions();
			this.otherActions();
		}

		/**
		 * Create the uploaders
		 */
		createUploaders() {
			var uploadHandler = Backbone.Model.extend(
				{
					initialize: function ($args) {
						this.uploaderOptions = $args;

						dlmUploaderInstance = this;
						const uploader      = new wp.Uploader(dlmUploaderInstance.uploaderOptions);
						// Dropzone events
						const dropzone      = uploader.dropzone;
						dropzone.on('dropzone:enter', dlmUploaderInstance.show);
						dropzone.on('dropzone:leave', dlmUploaderInstance.hide);

						uploader.uploader.bind('FilesAdded', dlmUploaderInstance.dlmFileAdded);
						uploader.uploader.bind('FileUploaded', dlmUploaderInstance.dlmAddFileToPath);
						uploader.uploader.bind('Error', dlmUploaderInstance.dlmUploadError);
						// File Uploading - update progress bar
						uploader.uploader.bind('UploadProgress', dlmUploaderInstance.uploadProgress);
					},
					/**
					 * Add the file url to File URLs meta
					 * @param {*} up
					 * @param {*} file
					 */
					dlmAddFileToPath: function (up, file) {
						const fileUrl  = file.attachment.attributes.url;

						// Check if is subjective upload or general one
						if ('plupload-browse-button' !== jQuery(up.settings.browse_button).attr('id')) {
							const fileURLs = jQuery(up.settings.browse_button).parents('.dlm-file-version__row').find('textarea');
							dlmUploaderInstance.endUploadProgress(fileURLs.parents('.dlm-file-version__row'));

							let filePaths = fileURLs.val();
							filePaths     = filePaths ? filePaths + "\n" + fileUrl : fileUrl;
							fileURLs.val(filePaths);
							dlmEditInstance.afterAddFile(fileURLs, file, up);
						} else {
							// It's a general update so we need to create a new File Version
							dlmEditInstance.addNewFile();

							// Attach file to newly created version
							jQuery(document).on('dlm_new_file_added', function (e) {
								const object         = jQuery(this);
								const versionWrapper = jQuery('.dlm-metaboxes.downloadable_files').find('.downloadable_file').first(),
									  fileURLs       = versionWrapper.find('textarea'),
									  version        = dlmUploaderInstance.retrieveVersion(file),
									  versionInpout  = versionWrapper.find('input[name*="downloadable_file_version"]');
								dlmUploaderInstance.endUploadProgress(jQuery(up.settings.container).parents('#dlm-new-upload'));

								fileURLs.val(fileUrl);
								if (null !== version) {
									versionInpout.val(version);
								}
								dlmEditInstance.afterAddFile(fileURLs, file, up);
								// Unbind event
								object.off(e);
							});
						}
					},
					/**
					 * Blur the textarea so the user knows it is loading
					 * @param {*} up
					 * @param {*} file
					 */
					dlmFileAdded: function (up, file) {
						if ('plupload-browse-button' !== jQuery(up.settings.browse_button).attr('id')) {
							const fileURLs = jQuery(up.settings.browse_button).parents('.dlm-file-version__row').find('textarea');
							dlmUploaderInstance.startUploadProgress(fileURLs.parents('.dlm-file-version__row'));
						} else {
							dlmUploaderInstance.startUploadProgress(jQuery(up.settings.container).parents('#dlm-new-upload'));
						}
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
					},
					/**
					 * Upload progress
					 *
					 * @param up
					 * @param file
					 */
					uploadProgress: function (up, file) {
						jQuery(up.settings.container).parent().parent().find('.dlm-uploading-file label span').html(up.total.percent + '%');
						jQuery(up.settings.container).parent().parent().find('.dlm-uploading-file .dlm-uploading-progress-bar').css({'width': up.total.percent + '%'});
					},
					/**
					 * Retrieve the version of the file
					 *
					 * @param $file
					 * @returns {{length}|*|null}
					 */
					retrieveVersion: function ($file) {
						const name = $file.name;
						// If name doesn't contain the `-` element it means it doesn't follow the naming convention
						// So no version can be retrieved
						if (name.indexOf('-') < 0) {
							return null;
						}

						let version   = name.split('-')[1];
						let extension = version.split('.');
						extension     = extension.pop();
						version       = version.slice(0, -(extension.length + 1));
						return version.length ? version : null;
					},
					/**
					 * Start uploading progress
					 *
					 * @param $element
					 */
					startUploadProgress: function ($element) {
						$element.find('.dlm-uploading-file').removeClass('hidden');
					},
					/**
					 * End uploading progress
					 *
					 * @param $element
					 */
					endUploadProgress: function ($element) {
						$element.find('.dlm-uploading-file label').toggleClass('hidden');

						setTimeout(function () {
							$element.find('.dlm-uploading-file').addClass('hidden');
							$element.find('.dlm-uploading-file label').toggleClass('hidden');
						}, 1500);
					}
				}
			);

			var EditorUploader = Backbone.View.extend(
				{
					tagName  : 'div',
					className: 'dlm-uploader-editor',
					template : wp.template('uploader-editor'),

					localDrag       : false,
					overContainer   : false,
					overDropzone    : false,
					draggingFile    : null,
					args            : {},
					elementContainer: null,

					/**
					 * Bind drag'n'drop events to callbacks.
					 */
					initialize: function ($args) {

						this.initialized      = false;
						this.args             = $args;
						this.elementContainer = jQuery(this.args.container[0]).attr('id');

						// Bail if not enabled or UA does not support drag'n'drop or File API.
						if (!window.tinyMCEPreInit || !window.tinyMCEPreInit.dragDropUpload || !this.browserSupport()) {
							return this;
						}

						this.$document = $(document);
						this.dropzone  = null;
						this.files     = [];
						this.$document.on('drop', '#' + this.elementContainer + ' .dlm-uploader-editor', _.bind(this.drop, this));
						this.$document.on('click', '#' + this.elementContainer + ' .dlm-uploader-editor', _.bind(this.click, this));
						this.$document.on('dragover', '#' + this.elementContainer + ' .dlm-uploader-editor', _.bind(this.dropzoneDragover, this));
						this.$document.on('dragleave', '#' + this.elementContainer + ' .dlm-uploader-editor', _.bind(this.dropzoneDragleave, this));

						this.$document.on('dragover', _.bind(this.containerDragover, this));
						this.$document.on('dragleave', _.bind(this.containerDragleave, this));

						this.$document.on('dragstart dragend drop', _.bind(function (event) {
							this.localDrag = event.type === 'dragstart';

							if (event.type === 'drop') {
								this.containerDragleave();
							}
						}, this));
						this.initialized = true;
						return this;
					},

					/**
					 * Check browser support for drag'n'drop.
					 *
					 * @return {boolean}
					 */
					browserSupport: function () {
						var supports = false, div = document.createElement('div');

						supports = ('draggable' in div) || ('ondragstart' in div && 'ondrop' in div);
						supports = supports && !!(window.File && window.FileList && window.FileReader);
						return supports;
					},

					isDraggingFile: function (event) {
						if (this.draggingFile !== null) {
							return this.draggingFile;
						}

						if (_.isUndefined(event.originalEvent) || _.isUndefined(event.originalEvent.dataTransfer)) {
							return false;
						}

						this.draggingFile = _.indexOf(event.originalEvent.dataTransfer.types, 'Files') > -1 &&
											_.indexOf(event.originalEvent.dataTransfer.types, 'text/plain') === -1;

						return this.draggingFile;
					},

					refresh: function (e) {

						// Hide the dropzones only if dragging has left the screen.
						this.dropzone.toggle(this.overContainer || this.overDropzone);

						if (!_.isUndefined(e)) {
							$(e.target).closest('.dlm-uploader-editor').toggleClass('droppable', this.overDropzone);
						}

						if (!this.overContainer && !this.overDropzone) {
							this.draggingFile = null;
						}

						return this;
					},

					render: function () {
						if (!this.initialized) {
							return this;
						}
						this.$el.html(this.template());
						jQuery('#' + this.elementContainer).append(this.$el);
						this.dropzone = this.$el;
						return this;
					},

					containerDragover: function (event) {

						if (this.localDrag || !this.isDraggingFile(event)) {
							return;
						}

						this.overContainer = true;
						this.refresh();
					},

					containerDragleave: function () {
						this.overContainer = false;

						// Throttle dragleave because it's called when bouncing from some elements to others.
						_.delay(_.bind(this.refresh, this), 50);
					},

					dropzoneDragover: function (event) {
						if (this.localDrag || !this.isDraggingFile(event)) {
							return;
						}

						this.overDropzone = true;
						this.refresh(event);
						return false;
					},

					dropzoneDragleave: function (e) {
						this.overDropzone = false;
						_.delay(_.bind(this.refresh, this, e), 50);
					},

					drop: function (event) {
						this.containerDragleave(event);
						this.dropzoneDragleave(event);
						return false;
					},

					click: function (e) {
						// In the rare case where the dropzone gets stuck, hide it on click.
						this.containerDragleave(e);
						this.dropzoneDragleave(e);
						this.localDrag = false;
					}
				}
			);

			dlmUploader['uploadHandlerModel'] = uploadHandler;
			dlmUploader['uploadHandlerView']  = EditorUploader;
		}

		/**
		 * Init already set uploaders.
		 */
		initUploaders() {

			const dlmNewUploaderOptions = {
				browser  : jQuery('#plupload-browse-button'),
				plupload : {
					multi_selection: false,
				},
				params   : {
					type: 'dlm_download'
				},
				container: jQuery('#drag-drop-area'),
				dropzone : jQuery('#drag-drop-area'),
			}

			const dlmNewUploadeFileModel = new dlmUploader['uploadHandlerModel'](dlmNewUploaderOptions),
				  dlmNewUploadeFileView  = new dlmUploader['uploadHandlerView'](dlmNewUploaderOptions);

			dlmNewUploadeFileView.render();
			dlmUploadButtons.push(jQuery('#plupload-browse-button'));

			$('.dlm_upload_file:not(#plupload-browse-button)').each((index, element) => {

				dlmUploadButtons.push($(element));

				const dlmUploaderOptions  = {
						  browser  : $(element),
						  plupload : {
							  multi_selection: false,
						  },
						  params   : {
							  type: 'dlm_download'
						  },
						  container: $(element).parents('div.dlm-uploader-container'),
						  dropzone : $(element).parents('div.dlm-uploader-container'),
					  },
					  dlmUploadeFileModel = new dlmUploader['uploadHandlerModel'](dlmUploaderOptions),
					  dlmUploadeFileView  = new dlmUploader['uploadHandlerView'](dlmUploaderOptions);

				dlmUploadeFileView.render();

			});
		}

		/**
		 * When adding a new file we need to initiate the newly created uploaders and we need to hide the new version upload functionality.
		 */
		newFileAction() {
			const instance = this;
			$(document).on('dlm_new_file_added', () => {

				$('.dlm_upload_file:not(#plupload-browse-button)').each((index, element) => {
					if (dlmUploadButtons.includes($(element))) {
						return true;
					}

					dlmUploadButtons.push($(element));

					const dlmUploaderOptions  = {
							  browser  : $(element),
							  plupload : {
								  multi_selection: false,
							  },
							  params   : {
								  type: 'dlm_download'
							  },
							  container: $(element).parents('div.dlm-uploader-container'),
							  dropzone : $(element).parents('div.dlm-uploader-container'),
						  },
						  dlmUploadeFileModel = new dlmUploader['uploadHandlerModel'](dlmUploaderOptions),
						  dlmUploadeFileView  = new dlmUploader['uploadHandlerView'](dlmUploaderOptions);

					dlmUploadeFileView.render();
				});
				jQuery('#dlm-new-upload').hide();

				const versions = jQuery('.downloadable_file');
				if ( 0 !== versions.length ) {
					jQuery('.dlm-versions-tab').show();
					jQuery('.dlm-versions-tab .dlm-versions-number').html( '(' + versions.length + ')' );
				}
			});
		}

		/**
		 * Removing a file should re-initiate the new version uploader functionality.
		 */
		removeFileAction() {
			$(document).on('dlm_remove_file', () => {
				const files = jQuery('.downloadable_files').find('.dlm-metabox.downloadable_file');
				if (0 === files.length) {
					jQuery('#dlm-new-upload').show();
				}
			});
		}

		/**
		 * Click actions for the Versions metabox
		 */
		clickActions() {
			const instance = this;

			// Open/close
			jQuery('.dlm-metaboxes-wrapper').on('click', '.dlm-metabox h3', function (event) {
				// If the user clicks on some form input inside the h3, like a select list (for variations), the box should not be toggled
				if (jQuery(event.target).filter(':input, option').length) return;
				const target = jQuery(this),
					  content = target.next('.dlm-metabox-content');
				target.toggleClass('opened');
				content.toggle();
				jQuery( '.dlm-metabox h3' ).not(target).removeClass('opened');
				jQuery('.dlm-metabox-content').not(content).hide();
			});

			// Add a file
			jQuery('.download_monitor_files').on('click', 'a.add_file', function (e) {
				e.preventDefault();
				instance.addNewFile();
			});

			// Remove a file
			jQuery('.download_monitor_files').on('click', '.remove_file', function (e) {
				e.preventDefault();
				var answer = confirm(dlm_ed_strings.confirm_delete);
				if (answer) {

					var el      = jQuery(this).closest('.downloadable_file');
					var file_id = el.attr('data-file');

					if (file_id > 0) {

						jQuery(el).block(
							{
								message   : null,
								overlayCSS: {
									background: '#fff url(' + $('#dlm-plugin-url').val() + '/assets/images/ajax-loader.gif) no-repeat center',
									opacity   : 0.6
								}
							}
						);

						var data = {
							action     : 'download_monitor_remove_file',
							file_id    : file_id,
							download_id: $('#dlm-post-id').val(),
							security   : $('#dlm-ajax-nonce-remove-file').val()
						};

						jQuery.post(
							ajaxurl,
							data,
							function (response) {
								jQuery(el).fadeOut('300').remove();
								jQuery(document).trigger('dlm_remove_file', [this, el]);
							}
						);

					} else {
						jQuery(el).fadeOut('300').remove();
					}
				}
				return false;
			});

			// Browse for file
			jQuery('.download_monitor_files').on('click', 'a.dlm_browse_for_file', function (e) {
				e.preventDefault();
				if (jQuery(this).parents('#dlm-new-upload').length > 0) {
					instance.addNewFile();
					// Attach file to newly created version
					jQuery(document).on('dlm_new_file_added', function (event) {
						const object             = jQuery(this);
						downloadable_files_field = jQuery('.downloadable_file').find('textarea[name^="downloadable_file_urls"]');

						window.send_to_editor = window.send_to_browse_file_url;

						tb_show(dlm_ed_strings.browse_file, 'media-upload.php?post_id=' + $('#dlm-post-id').val() + '&amp;type=downloadable_file_browser&amp;from=wpdlm01&amp;TB_iframe=true');
						// Unbind event
						object.off(event);
						dlmEditInstance.afterAddFile(downloadable_files_field);

						return false;
					});
				} else {
					downloadable_files_field = jQuery(this).closest('.downloadable_file').find('textarea[name^="downloadable_file_urls"]');

					window.send_to_editor = window.send_to_browse_file_url;

					tb_show(dlm_ed_strings.browse_file, 'media-upload.php?post_id=' + $('#dlm-post-id').val() + '&amp;type=downloadable_file_browser&amp;from=wpdlm01&amp;TB_iframe=true');
					dlmEditInstance.afterAddFile(downloadable_files_field);

					return false;
				}
			});


			// Custom URL
			jQuery('.download_monitor_files').on('click', 'a.dlm_external_source', function (e) {
				e.preventDefault();
				if (jQuery(this).parents('#dlm-new-upload').length > 0) {
					instance.addNewFile();
					// Attach file to newly created version
					jQuery(document).on('dlm_new_file_added', function (event) {
						const object             = jQuery(this);
						downloadable_files_field = jQuery('.downloadable_file').find('textarea[name^="downloadable_file_urls"]');

						// Unbind event
						object.off(event);
						dlmEditInstance.afterAddFile(downloadable_files_field);

						return false;
					});
				} else {
					downloadable_files_field = jQuery(this).closest('.downloadable_file').find('textarea[name^="downloadable_file_urls"]');

					dlmEditInstance.afterAddFile(downloadable_files_field);

					return false;
				}
			});

			// Uploading files
			var dlm_media_library_frame;

			jQuery(document).on('click', '.dlm_media_library', function (event) {
				event.preventDefault();
				var $el              = $(this);
				var $file_path_field = null;

				if (jQuery(this).parents('#dlm-new-upload').length > 0) {
					instance.addNewFile()
					jQuery(document).on('dlm_new_file_added', function (event) {
						const object     = jQuery(this);
						$file_path_field = jQuery('textarea.downloadable_file_urls');
						var file_paths   = '';
						instance.addBrowsedFile($el, $file_path_field, file_paths, dlm_media_library_frame);
						// Unbind event
						object.off(event);
						dlmEditInstance.afterAddFile($file_path_field);
					});

				} else {
					$file_path_field = $el.parents('.dlm-file-version__row').find('.downloadable_file_urls');
					var file_paths   = $file_path_field.val();
					instance.addBrowsedFile($el, $file_path_field, file_paths, dlm_media_library_frame);
					dlmEditInstance.afterAddFile($file_path_field);
				}

			});

			// Copy button functionality
			$('.copy-dlm-button').on('click', function (e) {
				e.preventDefault();
				var dlm_input = $(this).parent().find('input');
				dlm_input.focus();
				dlm_input.select();
				document.execCommand('copy');
				$(this).next('span').text($(this).data('item') + ' copied');
				$('.copy-dlm-button').not($(this)).parent().find('span').text('');
			});

			jQuery(document).on( 'dlm_remove_file', function (event, action, element ) {
				const versions = jQuery('.downloadable_file');
				if ( 0 === versions.length ) {
					jQuery('.dlm-versions-tab').hide();
				} else {
					jQuery('.dlm-versions-tab .dlm-versions-number').html( '(' + versions.length + ')' );
				}
			} );
		}

		/**
		 * Add new file
		 * @returns {boolean}
		 */
		addNewFile() {
			jQuery('.download_monitor_files').block(
				{
					message   : null,
					overlayCSS: {
						background: '#fff url(' + $('#dlm-plugin-url').val() + '/assets/images/ajax-loader.gif) no-repeat center',
						opacity   : 0.6
					}
				});

			var size = jQuery('.downloadable_files .downloadable_file').length;

			var data = {
				action  : 'download_monitor_add_file',
				post_id : $('#dlm-post-id').val(),
				size    : size,
				security: $('#dlm-ajax-nonce-add-file').val()
			};

			jQuery.post(ajaxurl, data, function (response) {

				jQuery('.downloadable_files').prepend(response);

				downloadable_file_row_indexes();

				jQuery('.download_monitor_files').unblock();

				// Date picker
				jQuery(".date-picker-field").datepicker(
					{
						dateFormat     : "yy-mm-dd",
						numberOfMonths : 1,
						showButtonPanel: true
					});

				jQuery(document).trigger('dlm_new_file_added', [this, response]);
			});

			return false;
		}

		/**
		 * Add browsed file from server browsing
		 * @param $el
		 * @param $file_path_field
		 * @param file_paths
		 * @param dlm_media_library_frame
		 */
		addBrowsedFile($el, $file_path_field, file_paths, dlm_media_library_frame) {
			// If the media frame already exists, reopen it.
			if (dlm_media_library_frame) {
				dlm_media_library_frame.close();
			}

			var downloadable_file_states = [
				// Main states.
				new wp.media.controller.Library(
					{
						library   : wp.media.query(),
						multiple  : true,
						title     : $el.data('choose'),
						priority  : 20,
						filterable: 'all',
					})
			];

			// Create the media frame.
			dlm_media_library_frame = wp.media.frames.downloadable_file = wp.media(
				{
					// Set the title of the modal.
					title   : $el.data('choose'),
					library : {
						type: ''
					},
					button  : {
						text: $el.data('update'),
					},
					multiple: true,
					states  : downloadable_file_states,
				});

			// When an image is selected, run a callback.
			dlm_media_library_frame.on('select', function () {

				var selection = dlm_media_library_frame.state().get('selection');

				selection.map(function (attachment) {

					attachment = attachment.toJSON();

					if (attachment.url)
						file_paths = file_paths ? file_paths + "\n" + attachment.url : attachment.url

				});

				$file_path_field.val(file_paths);
			});

			// Set post to 0 and set our custom type
			dlm_media_library_frame.on('ready', function () {
				dlm_media_library_frame.uploader.options.uploader.params = {
					type: 'dlm_download'
				};
			});

			// Finally, open the modal.
			dlm_media_library_frame.open();
		}

		/**
		 * Clean the area
		 *
		 * @param $element
		 */
		afterAddFile($element, file = null, up = null) {
			$element.parents('.dlm-file-version__row').find('.dlm-file-version__drag_and_drop').addClass('hidden');
			$element.parents('.dlm-file-version__row').find('.dlm-file-version__file_present').removeClass('hidden');
			if ( null !== file && null !== up ) {
				const file_id = file.attachment.id,
					  nonce   = $('#dlm-ajax-nonce-add-file').val(),
					  download_id = parseInt(jQuery('input#post_ID').val()),
					  version_id = $element.parents('.downloadable_files').find( '.downloadable_file' ).first().data('file'),
					  data    = {
						  action     : 'dlm_update_file_meta',
						  file_id    : file_id,
						  version_id : version_id,
						  download_id: download_id,
						  nonce      : nonce
					  };

				jQuery.post(ajaxurl, data, function (response) {
					if (!response.success) {
						console.log('Error saving attachment meta');
					}
				});
			}
		}
		/**
		 * Other functions that are used
		 *
		 * @since 4.7.4
		 */
		otherActions() {
			// Update the version number when version input changes
			jQuery(document).on('keyup', 'input[name^="downloadable_file_version"]', function () {
				const value = '' !== jQuery(this).val() ? jQuery(this).val() : 'n/a';

				jQuery(this).parents('.downloadable_file').find('.dlm-version-info__version').text(value);
			});
		}
	}


	// Closes all to begin
	jQuery('.dlm-metabox.closed').each(function () {
		jQuery(this).find('.dlm-metabox-content').hide();
	});

	// Date picker
	jQuery(".date-picker-field").datepicker(
		{
			dateFormat     : "yy-mm-dd",
			numberOfMonths : 1,
			showButtonPanel: true,
		}
	);

	// Ordering
	jQuery('.downloadable_files').sortable(
		{
			items               : '.downloadable_file',
			cursor              : 'move',
			axis                : 'y',
			handle              : 'h3',
			scrollSensitivity   : 40,
			forcePlaceholderSize: true,
			helper              : 'clone',
			opacity             : 0.65,
			placeholder         : 'dlm-metabox-sortable-placeholder',
			start               : function (event, ui) {
				ui.item.css('background-color', '#f6f6f6');
			},
			stop                : function (event, ui) {
				ui.item.removeAttr('style');
				downloadable_file_row_indexes();
			}
		}
	);

	function downloadable_file_row_indexes() {
		jQuery('.downloadable_files .downloadable_file').each(function (index, el) {
			jQuery('.file_menu_order', el).val(parseInt(jQuery(el).index('.downloadable_files .downloadable_file')));
		});
	}

	window.send_to_browse_file_url = function (html) {

		if (html) {
			old = jQuery.trim(jQuery(downloadable_files_field).val());
			if (old) old = old + "\n";
			jQuery(downloadable_files_field).val(old + html);
		}

		tb_remove();

		window.send_to_editor = window.send_to_editor_default;
	}
	new DLM_Edit_Download();
});