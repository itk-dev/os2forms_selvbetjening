import React from "react";
import * as PropTypes from "prop-types";
import "./calendar-cell-info-button.scss";

/**
 * Calendar cell information button component.
 *
 * @param {object} props Props.
 * @param {string} props.resource Resource object.
 * @param {Function} props.onClickEvent Resource click event
 * @returns {JSX.Element} Calendar cell information button component.
 */
function CalendarCellInfoButton({ resource, onClickEvent }) {
  return (
    <span className="calendar-cell-info">
      <button
        className="calendar-cell-info-button"
        type="button"
        onClick={() => {
          onClickEvent(resource);
        }}
      >
        {resource.title}
        <img
          height="15"
          width="15"
          alt="ressource information"
          src="data:image/png;base64,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"
        />
      </button>
    </span>
  );
}

CalendarCellInfoButton.propTypes = {
  resource: PropTypes.shape({
    title: PropTypes.string.isRequired,
  }).isRequired,
  onClickEvent: PropTypes.func.isRequired,
};

export default CalendarCellInfoButton;
