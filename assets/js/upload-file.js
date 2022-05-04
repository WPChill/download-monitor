jQuery(function ($) {

    /**
     * Set up the Media Uploader
     */

    let dlmUploadButtons = [];
    $('.dlm_upload_file').each((index, element) => {

        dlmUploadButtons.push($(element));

        const dlmUploaderOptions = {
                browser: $(element),
                plupload: {
                    multi_selection: false,
                },
                params: {
                    type: 'dlm_download'
                }
            },
            dlmUploader = new wp.Uploader(dlmUploaderOptions);

        dlmUploader.uploader.bind('FilesAdded', dlmFileAdded);
        dlmUploader.uploader.bind('FileUploaded', dlmAddFileToPath);
    });


    $(document).on('dlm_new_file_added', () => {

        $('.dlm_upload_file').each((index, element) => {

            if (dlmUploadButtons.includes($(element))) {
                return true;
            }

            dlmUploadButtons.push($(element));

            const dlmUploaderOptions = {
                    browser: $(element),
                    plupload: {
                        multi_selection: false,
                    },
                    params: {
                        type: 'dlm_download'
                    }
                },
                dlmUploader = new wp.Uploader(dlmUploaderOptions);
            dlmUploader.uploader.bind('FilesAdded', dlmFileAdded);
            dlmUploader.uploader.bind('FileUploaded', dlmAddFileToPath);
        });

    });

    /**
     * Add the file url to File URLs meta
     * @param {*} up 
     * @param {*} file 
     */
    function dlmAddFileToPath(up, file) {

        const fileUrl = file.attachment.attributes.url;
        const fileURLs = jQuery(up.settings.browse_button).parents('td').find('textarea');
        fileURLs.parent().removeClass('dlm-blury');
        let filePaths = fileURLs.val();
        filePaths = filePaths ? filePaths + "\n" + fileUrl : fileUrl;
        fileURLs.val(filePaths);
    }

    /**
     * Blur the textarea so the user knows it is loading
     * @param {*} up 
     * @param {*} file 
     */
    function dlmFileAdded(up, file) {

        const fileURLs = jQuery(up.settings.browse_button).parents('td').find('textarea');
        fileURLs.parent().addClass('dlm-blury');
    }
});