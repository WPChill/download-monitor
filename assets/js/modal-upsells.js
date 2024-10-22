/**
 * Class to handle modal upsells
 *
 * @since 5.0.13
 */
class DlmModalUpsells {
  upsells = [];

  /**
   * Constructor
   *
   * @since 5.0.13
   */
  constructor() {
    this.init();
  }

  /**
   * Initialize
   *
   * @since 5.0.13
   */
  init() {
    const instance = this;
    instance.upsells = dlmModalUpsellsVars.upsells;
    instance.bindEvents();
  }

  /**
   * Bind events
   *
   * @since 5.0.13
   */
  bindEvents() {
    const instance = this;
    if ("0" !== instance.upsells.length) {
      // Bind click event to open modal
      for (let key in instance.upsells) {
        jQuery("body").on(
          "click",
          `a[href='${key}_upsell_modal']`,
          function (e) {
            e.preventDefault();
            instance.openModal(key);
          }
        );
        // Bind click event to close modal
        jQuery("body").on(
          "click",
          `.dlm-modal__overlay.${key}, .dlm-modal__overlay.${key} .dlm-modal__dismiss`,
          function (e) {
            e.preventDefault();
            instance.closeModal(key);
          }
        );
      }
    }
  }

  /**
   * Open modal
   *
   * @since 5.0.13
   */
  openModal(upsell) {
    const data = {
      action: "dlm_upsell_modal",
      security: dlmModalUpsellsVars.security,
      upsell: upsell,
    };
    jQuery.post(ajaxurl, data, function (response) {
      const $body = jQuery("body");
      $body.addClass("modal-open");
      if ("undefined" !== response.data.content) {
        $body.append(response.data.content);
      }
    });
  }

  /**
   * Close modal
   *
   * @since 5.0.13
   */
  closeModal(upsell) {
    jQuery(`.dlm-modal__overlay.${upsell}`).remove();
    jQuery("body").removeClass("modal-open");
  }
}

// Load the class when window loaded
jQuery(document).ready(function () {
  new DlmModalUpsells();
});
