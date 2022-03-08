jQuery(function ($) {

    /**
     * Set up the Media Uploader
     */
    $('.dlm_upload_file').each((index, element) => {

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

        dlmUploader.uploader.bind('FileUploaded', dlmAddFileToPath);
    });

    /**
     * Add the file url to File URLs meta
     * @param {*} up 
     * @param {*} file 
     */
    function dlmAddFileToPath(up, file) {

        const fileUrl = file.attachment.attributes.url;
        const fileURLs = jQuery( up.settings.browse_button ).parents('td').find('textarea');
        let filePaths = fileURLs.val();
        filePaths = filePaths ? filePaths + "\n" + fileUrl : fileUrl;
        fileURLs.val(filePaths);
    }
});