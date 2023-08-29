import React from "react";
import * as PropTypes from "prop-types";
import dayjs from "dayjs";

/**
 * Calendar selection box component.
 *
 * @param {object} props Props.
 * @param {object} props.calendarSelection Object containing selection info returned by fullcalendar
 * @param {string} props.calendarSelectionResourceTitle Title of selected resource.
 * @param {number} props.calendarSelectionResourceId Id of selected resource
 * @param {string} props.actionText Text for action button.
 * @returns {object} Calendar selection box
 */
function CalendarSelectionBox({
  calendarSelection,
  calendarSelectionResourceTitle,
  calendarSelectionResourceId,
  actionText,
}) {
  /**
   * @param {string} startStr String containing the start-dateTime of the selection.
   * @returns {string} Date formatted as string.
   */
  function getFormattedDate(startStr) {
    return dayjs(startStr).format("dddd [d.] D. MMMM YYYY");
  }

  /**
   * @param {string} startStr String containing the start-dateTime of the selection
   * @param {string} endStr String containing the end-dateTime of the selection
   * @returns {string} Time formatted as string.
   */
  function getFormattedTime(startStr, endStr) {
    const formattedTimeStart = dayjs(startStr).format("HH:mm");
    const formattedTimeEnd = dayjs(endStr).format("HH:mm");

    return `kl. ${formattedTimeStart} til ${formattedTimeEnd}`;
  }

  return (
    <div id="calendar-selection-dot">
      <div id="calendar-selection-line">
        <div id="calendar-selection-container">
          <button id="calendar-selection-close" type="button">
            x
          </button>
          <span id="calendar-selection-choice">Dit valg</span>
          <span id="calendar-selection-choice-title">
            <b>{calendarSelectionResourceTitle}</b>
          </span>
          <span>
            <b>{getFormattedDate(calendarSelection.start)}</b>
          </span>
          <span>
            <b>{getFormattedTime(calendarSelection.start, calendarSelection.end)}</b>
          </span>
          <button id="calendar-selection-choice-confirm" data-resource-id={calendarSelectionResourceId} type="button">
            {actionText}
          </button>
        </div>
      </div>
    </div>
  );
}

CalendarSelectionBox.defaultProps = {
  actionText: "Fortsæt med dette valg",
};

CalendarSelectionBox.propTypes = {
  calendarSelection: PropTypes.shape({
    start: PropTypes.shape({
      toISOString: PropTypes.func.isRequired,
    }),
    end: PropTypes.shape({
      toISOString: PropTypes.func.isRequired,
    }),
    resourceId: PropTypes.string,
  }).isRequired,
  calendarSelectionResourceTitle: PropTypes.string.isRequired,
  calendarSelectionResourceId: PropTypes.number.isRequired,
  actionText: PropTypes.string,
};

export default CalendarSelectionBox;
