jQuery( function ( $ ) {
    $('.dlm-metaboxes.downloadable_files').on('click', '.dlm_upload_file', function(e){
        e.preventDefault();
        button_elem = this;
        openFileDialog(selected_callback);

     });
        
        function openFileDialog(callback) { 

            // Create an input element
            var inputElement = document.createElement("input");

            // Set its type to file
            inputElement.type = "file";

            // set onchange event to call callback when user has selected file
            inputElement.addEventListener("change", callback)

            // dispatch a click event to open the file dialog
            inputElement.dispatchEvent(new MouseEvent("click")); 
        }

        var selected_callback = function selectedFileAction( event ){
            
            [...this.files].forEach(file => {
            var formData = new FormData();

            formData.append("file", file, file.name);
            formData.append('action', 'dlm_upload_file');

            $.ajax({
                type: "POST",
                url: dlm_ajaxurl.ajax_url,
                success: function (data) {

                    var $el = $( button_elem );
                    var $file_path_field = $el.parent().parent().find( '.downloadable_file_urls' );
                    var file_paths = $file_path_field.val();
                    file_paths = file_paths ? file_paths + "\n" + data.data.file_url : data.data.file_url;
                    $file_path_field.val( file_paths );
                    
                },
                error: function (error) {
                    var $el = $( button_elem );
                    var $file_path_field = $el.parent().parent().find( '.downloadable_file_urls' );
                    $file_path_field.parent().append('<div class="notice notice-error"><p>' + error.data.errorMessage + '</p></div>');
                },
                async: true,
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                timeout: 60000
            });
        });
        }
} );