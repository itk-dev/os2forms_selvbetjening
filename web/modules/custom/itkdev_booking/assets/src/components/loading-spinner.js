import React from "react";
import * as PropTypes from "prop-types";
import "./loading-spinner.scss";

/**
 * Loading spinner component.
 *
 * @param {object} props Props.
 * @param {string} props.size Size of spinner.
 * @returns {JSX.Element} Loading spinner component.
 */
function LoadingSpinner({ size = "default" }) {
  return (
    <div className={`${size === "small" ? "small-loader" : "loader"}`}>
      {/* TODO: Replace with svg */}
      <img
        src="data:image/png;base64,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"
        alt="loading"
      />
    </div>
  );
}

LoadingSpinner.defaultProps = {
  size: "default",
};

LoadingSpinner.propTypes = {
  size: PropTypes.string,
};

export default LoadingSpinner;
