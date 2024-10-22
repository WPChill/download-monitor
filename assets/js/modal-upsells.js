jQuery(function ($) {
  /**
   * Class to handle modal upsells
   *
   * @since 5.0.13
   */
  class dlmModalUpsells {
    upsells = [];

    /**
     * Constructor
     *
     * @since 5.0.13
     */
    constructor() {
      console.log("test");
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
          $("body").on("click", `a[href='${key}_upsell_modal']`, function (e) {
            e.preventDefault();
            instance.openModal(key);
          });
          // Bind click event to close modal
          $("body").on(
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
      $.post(ajaxurl, data, function (response) {
        const $body = $("body");
        $body.addClass("modal-open");
        $body.append(response);
      });
    }

    /**
     * Close modal
     *
     * @since 5.0.13
     */
    closeModal(upsell) {
      $(`.dlm-modal__overlay.${upsell}`).remove();
      $("body").removeClass("modal-open");
    }
  }

  // Load the class when window loaded
  $(document).ready(function () {
    new dlmModalUpsells();
  });
});
