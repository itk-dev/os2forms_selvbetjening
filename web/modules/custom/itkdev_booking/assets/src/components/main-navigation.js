import React, { useEffect, useState } from "react";
import * as PropTypes from "prop-types";
import "./main-navigation.scss";

/**
 * Main navigation component.
 *
 * @param {object} props Props.
 * @param {object} props.config Object containing configuration from drupal
 * @returns {JSX.Element} Info box component
 */
function MainNavigation({ config }) {
  const [createBookingUrl, setCreateBookingUrl] = useState("");
  const [changeBookingUrl, setChangeBookingUrl] = useState("");

  useEffect(() => {
    if (config.create_booking_url) {
      setCreateBookingUrl(config.create_booking_url);
    } else {
      setCreateBookingUrl(window.location.href);
    }

    if (config.change_booking_url) {
      setChangeBookingUrl(config.change_booking_url);
    } else {
      setChangeBookingUrl(window.location.href);
    }
  }, [config]);

  const activeUrl = window.location.href;

  return (
    <div className="row">
      <div className="col main-navigation-content">
        <ul>
          {createBookingUrl !== "" && (
            <li>
              <a
                href={createBookingUrl}
                className={`main-navigation-link ${activeUrl === createBookingUrl ? "active" : "inactive"}`}
              >
                Opret ny booking
              </a>
            </li>
          )}
          {changeBookingUrl !== "" && (
            <li>
              <a
                href={changeBookingUrl}
                className={`main-navigation-link ${activeUrl === changeBookingUrl ? "active" : "inactive"}`}
              >
                Mine bookinger
              </a>
            </li>
          )}
        </ul>
      </div>
    </div>
  );
}

MainNavigation.propTypes = {
  config: PropTypes.shape({
    create_booking_url: PropTypes.string,
    change_booking_url: PropTypes.string,
  }).isRequired,
};

export default MainNavigation;
