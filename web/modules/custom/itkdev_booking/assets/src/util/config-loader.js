/** Config loader. */
export default class ConfigLoader {
  static async loadConfig() {
    // Load config from drupalSettings if available.
    if (window?.drupalSettings?.booking_app) {
      return window.drupalSettings.booking_app;
    }

    // Loading from config file in public folder.
    return fetch("config.json")
      .then((response) => response.json())
      .catch(() => {
        // Load defaults.
        return {
          api_endpoint: "https://selvbetjening.local.itkdev.dk/",
          element_id: "booking",
          front_page_url: "https://selvbetjening.local.itkdev.dk/",
          license_key: "",
          enable_booking: true,
          enable_resource_tooltips: true,
          output_field_id: "submit-values",
          info_box_color: "#0C6EFD",
          info_box_header: "Bemærk Behandlingstid!",
          info_box_content: "Til godkendelse og nøgleudlevering. Det vil fremgå af lokalets information.",
          step_one: false,
          redirect_url: "http://google.com/",
          create_booking_mode: true,
          create_booking_url: "http://bookingapp.local.itkdev.dk/createbookingurl",
          change_booking_url: "http://bookingapp.local.itkdev.dk/changebookingurl",
        };
      });
  }
}
