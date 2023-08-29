import { toast } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";

/** @param {string} text The toast display text */
export function displaySuccess(text) {
  toast.success(text);
}

/**
 * @param {string} errorString - The toast display text
 * @param {object} error - The error
 */
export function displayError(errorString, error) {
  const displayText = `${errorString}

  "${error}"`;

  toast.error(displayText, {
    autoClose: false,
  });
}
