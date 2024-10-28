// Load the translations
const { __ } = wp.i18n;
const submitButton = jQuery("#wp-submit"),
  closeModalButton = jQuery("#dlm-no-access-modal .dlm-no-access-modal-close");
submitButton.on("click", function (event) {
  event.preventDefault();
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
    };
  // Check if user name is empty
  if (!userName || !userName.val()) {
    alert(__("User name is required", "download-monitor")); // User name is required
    return;
  }
  // Check if password is empty
  if (!password || !password.val()) {
    alert(__("Password is required", "download-monitor"));
    return;
  }
  // Make AJAX request
  jQuery.ajax({
    type: "POST",
    url: memberLock.ajaxurl,
    data: data,
    success: function (response) {
      // Close the modal
      closeModalButton.trigger("click");
    },
    error: function (error) {
      console.error(error);
    },
  });
});
