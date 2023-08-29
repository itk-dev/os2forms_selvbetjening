import React from "react";
import * as PropTypes from "prop-types";
import { ReactComponent as InitialState } from "../assets/initialstate.svg";
import { ReactComponent as EmptyState } from "../assets/emptystate.svg";

/**
 * @param {object} props Props
 * @param {string} props.state Defining the state of the overlay
 * @returns {JSX.Element} Overlay visualizing a zero-hit result
 */
function NoResultOverlay({ state }) {
  return (
    <div className="no-result-overlay">
      {state === "initial" && (
        <div>
          <div className="no-initial-image">
            <InitialState />
          </div>
          <div className="no-result-text">
            <span>Du har endnu ikke startet en søgning.</span>
            <span>Brug filtrene ovenfor for at vælge hvad du vil søge på.</span>
          </div>
        </div>
      )}
      {state === "noresult" && (
        <div>
          <div className="no-result-image">
            <EmptyState />
          </div>
          <div className="no-result-text">
            <span>Din søgning giver desværre ikke nogen resultater.</span>
            <span>Prøv at ændre på filtrene i toppen.</span>
          </div>
        </div>
      )}
    </div>
  );
}

NoResultOverlay.propTypes = {
  state: PropTypes.string.isRequired,
};

export default NoResultOverlay;
