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

	$('.dlm_upload_file').each((index, element) => {

		dlmUploadButtons.push($(element));

		const dlmUploaderOptions  = {
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
			  dlmUploadeFileModel = new dlmUploader['uploadHandlerModel'](dlmUploaderOptions),
			  dlmUploadeFileView  = new dlmUploader['uploadHandlerView'](dlmUploaderOptions);

		dlmUploadeFileView.render();

	});

	$(document).on('dlm_new_file_added', () => {

		$('.dlm_upload_file').each((index, element) => {

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
					  container: $(element).parents('table.dlm-metabox-content'),
					  dropzone : $(element).parents('table.dlm-metabox-content'),
				  },
				  dlmUploadeFileModel = new dlmUploader['uploadHandlerModel'](dlmUploaderOptions),
				  dlmUploadeFileView  = new dlmUploader['uploadHandlerView'](dlmUploaderOptions);

			dlmUploadeFileView.render();
		});
	});
});