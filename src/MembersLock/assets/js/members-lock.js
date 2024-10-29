jQuery("#wp-submit").on("click", function (event) {
  event.preventDefault();
  const closeModalButton = jQuery(
      "#dlm-no-access-modal .dlm-no-access-modal-close"
    ),
    modalContent = jQuery(
      "#dlm-no-access-modal .dlm-modal-content #dlm_login_form"
    );
  // Get user name
  const userName = jQuery("#user_login"),
    // Get password
    password = jQuery("#user_pass"),
    // Create AJAX args
    data = {
      action: "dlm_login_member",
      security: memberLock.nonce,
      user_name: userName.val(),
      user_pass: password.val(),
      download_id: jQuery("#download_id").val(),
    };
  // Check if user name is empty
  if (!userName || !userName.val()) {
    alert(dlmMembersLockLang.required_user); // User name is required
    return;
  }
  // Check if password is empty
  if (!password || !password.val()) {
    alert(dlmMembersLockLang.required_pass); // Password is required
    return;
  }
  // Make AJAX request
  jQuery.ajax({
    type: "POST",
    url: memberLock.ajaxurl,
    data: data,
    success: function (response) {
      if (response.success) {
        modalContent.find(".dlm_tc_form").remove();
        modalContent.append(response.data);
      } else {
        modalContent.find(".dlm-errror-message").remove();
        modalContent.append(
          '<div class="dlm-error-message dlm-mt-6 dlm-text-center dlm-text-sm dlm-font-bold dlm-leading-9 dlm-tracking-tight dlm-text-gray-900">' +
            response.data +
            "</div>"
        );
      }
    },
    error: function (error) {
      console.error(error);
    },
  });
});
