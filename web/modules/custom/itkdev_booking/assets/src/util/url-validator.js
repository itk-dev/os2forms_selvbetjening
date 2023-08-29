import dayjs from "dayjs";
import { hasOwnProperty } from "./helpers";

/** Url validator. */
export default class UrlValidator {
  static valid(urlParams) {
    if (
      !urlParams ||
      !hasOwnProperty(urlParams, "from") ||
      !hasOwnProperty(urlParams, "to") ||
      !hasOwnProperty(urlParams, "resourceMail") ||
      !hasOwnProperty(urlParams, "resource")
    ) {
      return false;
    }

    return (
      dayjs(urlParams.from, "YYYY-MM-DDTHH:MN:SS", true).isValid() &&
      dayjs(urlParams.to, "YYYY-MM-DDTHH:MN:SS", true).isValid()
    );
  }
}
