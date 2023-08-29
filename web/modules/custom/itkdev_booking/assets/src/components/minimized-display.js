import React from "react";
import dayjs from "dayjs";
import * as PropTypes from "prop-types";
import "./minimized-display.scss";

/**
 * Minimized display component.
 *
 * @param {object} props Props.
 * @param {Function} props.setDisplayState Set display state function.
 * @param {object} props.resource Resource.
 * @param {object} props.calendarSelection A selection in calendar.
 * @returns {JSX.Element} Calendar header component.
 */
function MinimizedDisplay({ setDisplayState, resource, calendarSelection }) {
  const onChangeBooking = () => setDisplayState("maximized");
  const formatUrlDate = (dateString) => dayjs(dateString).format("DD/MM/YYYY - HH:mm");

  return (
    <div className="col-md-12">
      <div className="row">
        <div className="minimized-display col-md-12">
          <div>
            <span className="location">{resource.locationDisplayName ?? resource.location}</span>
            <span className="subject">{resource.resourceDisplayName ?? resource.resourceName}</span>
          </div>
          <div>
            <span>{formatUrlDate(calendarSelection.start)}</span>
            <span>→</span>
            <span>{formatUrlDate(calendarSelection.end)}</span>
          </div>
          <div>
            <button id="change-booking" type="button" onClick={onChangeBooking}>
              Ændrér valg
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

MinimizedDisplay.propTypes = {
  setDisplayState: PropTypes.func.isRequired,
  resource: PropTypes.shape({
    location: PropTypes.string.isRequired,
    locationDisplayName: PropTypes.string,
    resourceName: PropTypes.string,
    resourceDisplayName: PropTypes.string.isRequired,
  }).isRequired,
  calendarSelection: PropTypes.shape({
    start: PropTypes.shape({}),
    end: PropTypes.shape({}),
  }).isRequired,
};

export default MinimizedDisplay;
