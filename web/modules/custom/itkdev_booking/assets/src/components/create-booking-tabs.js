import React from "react";
import "react-toastify/dist/ReactToastify.css";
import * as PropTypes from "prop-types";

/**
 * CreateBookingTabs component.
 *
 * @param {object} props The props
 * @param {string} props.activeTab Name of the active tab.
 * @param {Function} props.onTabChange Handle change of tab.
 * @param {boolean} props.disabled Disable filters.
 * @returns {JSX.Element} Component.
 */
function CreateBookingTabs({ activeTab, onTabChange, disabled }) {
  const onTabClick = (event) => {
    const tab = event.target.getAttribute("data-view");

    onTabChange(tab);
  };

  return (
    <div className={`row viewswapper-wrapper ${disabled ? "disable-wrapper" : ""}`}>
      <div className="viewswapper-container">
        <button
          type="button"
          onClick={onTabClick}
          data-view="calendar"
          className={activeTab === "calendar" ? "active booking-btn" : "booking-btn"}
        >
          Kalender
        </button>
        <button
          type="button"
          onClick={onTabClick}
          data-view="list"
          className={activeTab === "list" ? "active booking-btn" : "booking-btn"}
        >
          Liste
        </button>
        <button
          type="button"
          onClick={onTabClick}
          data-view="map"
          className={activeTab === "map" ? "active booking-btn" : "booking-btn"}
        >
          Kort
        </button>
      </div>
    </div>
  );
}

CreateBookingTabs.propTypes = {
  activeTab: PropTypes.string.isRequired,
  onTabChange: PropTypes.func.isRequired,
  disabled: PropTypes.bool.isRequired,
};

export default CreateBookingTabs;
