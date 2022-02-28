jQuery( function ( $ ) {
    $('.dlm-metaboxes.downloadable_files').on('click', '.dlm_upload_file', function(e){
        e.preventDefault();
        button_elem = this;
        DLMopenFileDialog(dlm_selected_file);

     });
        
        function DLMopenFileDialog(callback) { 

            // Create an input element
            var inputElement = document.createElement("input");

            // Set its type to file
            inputElement.type = "file";

            // set onchange event to call callback when user has selected file
            inputElement.addEventListener("change", callback)

            // dispatch a click event to open the file dialog
            inputElement.dispatchEvent(new MouseEvent("click")); 
        }

        var dlm_selected_file = function DLMselectedFileAction( event ){
            
            [...this.files].forEach(file => {
            var formData = new FormData();

            formData.append("file", file, file.name);
            formData.append('action', 'dlm_upload_file');

            if ( max_file_size > file.size ){
                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    success: function (data) {

                        if( data.success == true ){

                            var $el = $( button_elem );
                            var $file_path_field = $el.parent().parent().find( '.downloadable_file_urls' );
                            var file_paths = $file_path_field.val();
                            file_paths = file_paths ? file_paths + "\n" + data.data.file_url : data.data.file_url;
                            $file_path_field.val( file_paths );
                        }else if( data.success == false ){

                            var $el = $( button_elem );
                            var $file_path_field = $el.parent().parent().find( '.downloadable_file_urls' );
                            $file_path_field.parent().append('<div class="notice notice-error dlm-upload-notices"><p>' + data.data.errorMessage + '</p></div>');
                        }
                        
                    },
                    error: function () {
                        var $el = $( button_elem );
                        var $file_path_field = $el.parent().parent().find( '.downloadable_file_urls' );
                        $file_path_field.parent().append('<div class="notice notice-error dlm-upload-notices"><p>An error occurred while uploading the file. Please try again.</p></div>');
                    },
                    async: true,
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    timeout: 60000
                });
            }else{
                $( ".dlm-upload-notices" ).remove();
                var $el = $( button_elem );
                var $file_path_field = $el.parent().parent().find( '.downloadable_file_urls' );
                $file_path_field.parent().append('<div class="notice notice-error dlm-upload-notices"><p>The file size exceeds the max_upload_file_size limit. Max: '+max_file_size+'</p></div>'); 
            }
        });
        }
} );