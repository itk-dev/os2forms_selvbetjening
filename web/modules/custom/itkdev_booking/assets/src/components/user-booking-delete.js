import React, { useState } from "react";
import dayjs from "dayjs";
import * as PropTypes from "prop-types";
import Api from "../util/api";
import { displayError, displaySuccess } from "../util/display-toast";
import LoadingSpinner from "./loading-spinner";

/**
 * @param {object} props Props.
 * @param {object} props.config App config.
 * @param {object} props.booking Booking to delete.
 * @param {Function} props.onBookingDeleted Callback when booking has been deleted.
 * @param {Function} props.close Callback to close delete component without action.
 * @returns {JSX.Element} Component.
 */
function UserBookingDelete({ config, booking, onBookingDeleted, close }) {
  const [loading, setLoading] = useState(false);

  /**
   * Delete the booking.
   *
   * @param {object} bookingToDelete Booking to request deletion of.
   */
  const requestDeletion = (bookingToDelete) => {
    const bookingId = bookingToDelete.id;

    if (bookingId) {
      setLoading(true);

      Api.deleteBooking(config.api_endpoint, bookingId)
        .then(() => {
          displaySuccess("Sletning af booking lykkedes");

          onBookingDeleted(bookingId);
        })
        .catch((err) => {
          displayError("Sletning af booking fejlede", err);
        })
        .finally(() => {
          setLoading(false);
        });
    }
  };

  /**
   * Format date to string.
   *
   * @param {Date} dateObj Date for format.
   * @returns {string} Date formatted as string.
   */
  function getFormattedDateTime(dateObj) {
    return dayjs(dateObj).format("D/M [kl.] HH:mm");
  }

  return (
    <div className="main-container">
      {loading && <LoadingSpinner />}
      {!loading && (
        <div className="no-gutter col-md-12" style={{ padding: "1em" }}>
          <h2>Slet booking</h2>
          <div className="row">
            <div className="col small-padding" style={{ width: "100%" }}>
              <div style={{ margin: "1em 0" }}>
                <div>
                  <strong>Resource: </strong>
                  {booking.resourceDisplayName}
                </div>
                <div>
                  <strong>Titel på booking: </strong>
                  {booking.subject}
                </div>
                <div>
                  <strong>Tidspunkt: </strong>
                  {getFormattedDateTime(booking.start)} - {getFormattedDateTime(booking.end)}
                </div>

                <div style={{ margin: "1em 0" }}>
                  <strong>Er du sikker på, at du vil slette bookingen?</strong>
                </div>

                <button type="button" onClick={() => requestDeletion(booking)} style={{ margin: "0 .5em 0 0" }}>
                  Ja, slet den!
                </button>
                <button type="button" onClick={close}>
                  Annullér
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

UserBookingDelete.propTypes = {
  config: PropTypes.shape({
    api_endpoint: PropTypes.string.isRequired,
  }).isRequired,
  booking: PropTypes.shape({
    id: PropTypes.string.isRequired,
    resourceDisplayName: PropTypes.string.isRequired,
    subject: PropTypes.string.isRequired,
    start: PropTypes.string.isRequired,
    end: PropTypes.string.isRequired,
  }).isRequired,
  onBookingDeleted: PropTypes.func.isRequired,
  close: PropTypes.func.isRequired,
};

export default UserBookingDelete;
