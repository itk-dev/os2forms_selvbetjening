import React, { useState } from "react";
import * as PropTypes from "prop-types";
import "./info-box.scss";

/**
 * Information box component.
 *
 * @param {object} props Props.
 * @param {object} props.config Object containing configuration from drupal
 * @returns {JSX.Element} Info box component
 */
function InfoBox({ config }) {
  const [display, setDisplay] = useState(true);
  const infoBoxColor = config.info_box_color;
  const infoBoxHeader = config.info_box_header;
  const infoBoxContent = config.info_box_content;

  return (
    display && (
      <div className="info-box-wrapper">
        <div className="row info-box" style={{ backgroundColor: `${infoBoxColor}em` }}>
          <div className="col-md-11 col-sm-11 col-xs-11 info-box-content">
            <span className="info-box-content-header">
              <b>{infoBoxHeader}</b>
            </span>
            <span className="info-box-content-text">{infoBoxContent}</span>
          </div>
          <div
            className="col-md-1 col-sm-1 col-xs-1 info-box-close"
            onClick={() => setDisplay(false)}
            role="presentation"
          >
            <span>x</span>
          </div>
        </div>
      </div>
    )
  );
}

InfoBox.propTypes = {
  config: PropTypes.shape({
    info_box_color: PropTypes.string.isRequired,
    info_box_header: PropTypes.string.isRequired,
    info_box_content: PropTypes.string.isRequired,
  }).isRequired,
};

export default InfoBox;
