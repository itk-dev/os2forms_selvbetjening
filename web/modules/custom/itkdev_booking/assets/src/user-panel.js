import React, { useEffect, useState } from "react";
import dayjs from "dayjs";
import * as PropTypes from "prop-types";
import Api from "./util/api";
import LoadingSpinner from "./components/loading-spinner";
import { displayError } from "./util/display-toast";
import UserBookingEdit from "./components/user-booking-edit";
import UserBookingDelete from "./components/user-booking-delete";
import "./user-panel.scss";
import MainNavigation from "./components/main-navigation";

/**
 * @param {object} props Props.
 * @param {object} props.config App config.
 * @returns {JSX.Element} Component.
 */
function UserPanel({ config }) {
  const [loading, setLoading] = useState(true);
  const [userBookings, setUserBookings] = useState(null);
  const [editBooking, setEditBooking] = useState(null);
  const [deleteBooking, setDeleteBooking] = useState(null);
  const [changedBookingId, setChangedBookingId] = useState(null);
  const [sortField, setSortField] = useState("start");
  const [sortDirection, setSortDirection] = useState("desc");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(0);
  const pageSize = 10;

  const onBookingChanged = (bookingId, start, end) => {
    setEditBooking(null);

    setChangedBookingId(bookingId);

    const booking = userBookings.find((el) => el.id === bookingId);

    if (booking) {
      booking.start = start;

      booking.end = end;
    }
  };

  const onBookingDeleted = (bookingId) => {
    setDeleteBooking(null);

    const newUserBookings = userBookings.filter((el) => el.id !== bookingId);

    setUserBookings(newUserBookings);
  };

  const close = () => {
    setDeleteBooking(null);

    setEditBooking(null);
  };

  /**
   * @param {Date} dateObj Date for format.
   * @returns {string} Date formatted as string.
   */
  function getFormattedDateTime(dateObj) {
    return dayjs(dateObj).format("dddd [d.] D/M [kl.] HH:mm");
  }

  const fetchSearch = () => {
    if (config) {
      setLoading(true);

      Api.fetchUserBookings(config.api_endpoint, search, page, pageSize)
        .then((loadedUserBookings) => {
          setUserBookings(loadedUserBookings);
        })
        .catch((fetchUserBookingsError) => {
          displayError("Der opstod en fejl. Prøv igen senere...", fetchUserBookingsError);
        })
        .finally(() => {
          setLoading(false);
        });
    }
  };

  const submitSearch = (event) => {
    event.preventDefault();

    event.stopPropagation();

    if (page !== 0) {
      // This automatically triggers a search.
      setPage(0);
    } else {
      fetchSearch();
    }
  };

  useEffect(() => {
    if (page !== null) {
      fetchSearch();
    }
  }, [page]);

  const currentBookings = userBookings ? Object.values(userBookings["hydra:member"]) ?? [] : [];

  const getStatus = (status) => {
    switch (status) {
      case "ACCEPTED":
        return "Godkendt";
      case "DECLINED":
        return "Afvist";
      case "AWAITING_APPROVAL":
        return "Afventer godkendelse";
      default:
        return "Ukendt status";
    }
  };

  const sortBookings = (bookings, field, direction) => {
    const clonedBookings = [...bookings];

    let result = clonedBookings.sort((a, b) => {
      return a[field] - b[field];
    });

    if (direction === "desc") {
      result = result.reverse();
    }

    return result;
  };

  const setSort = (field) => {
    if (sortField === field) {
      setSortDirection(sortDirection === "asc" ? "desc" : "asc");
    } else {
      setSortDirection("desc");

      setSortField(field);
    }
  };

  const renderSortingButton = (field, title) => {
    return (
      <button
        type="button"
        onClick={() => setSort(field)}
        className={`userbookings-sorting${sortField === field ? " active" : ""}`}
      >
        {sortField === field && (sortDirection === "asc" ? "↑ " : "↓ ")}
        {title}
      </button>
    );
  };

  const renderBooking = (booking) => {
    const now = new Date();
    const bookingEnd = new Date(booking.end);

    return (
      <div className={`user-booking${bookingEnd < now ? " expired" : ""}`} key={booking.id}>
        <div>
          {booking.id === changedBookingId && <>Ændring gennemført.</>}
          <span className="location">{booking.displayName}</span>
          <span className="subject">{booking.subject}</span>
          <span className="status">{getStatus(booking.status)}</span>
        </div>
        <div>
          <span>{getFormattedDateTime(booking.start)}</span>
          <span>→</span>
          <span>{getFormattedDateTime(booking.end)}</span>
        </div>

        {bookingEnd < now && <div>Booking er udløbet</div>}

        {bookingEnd >= now && (
          <div>
            <button type="button" onClick={() => setDeleteBooking(booking)}>
              Anmod om sletning
            </button>
            <button type="button" onClick={() => setEditBooking(booking)}>
              Anmod om ændring af tidspunkt
            </button>
          </div>
        )}
      </div>
    );
  };

  const onFilterChange = (event) => {
    setSearch(event.target.value);
  };

  const addPage = (value) => {
    const newValue = page + value;

    if (newValue < 0 || newValue > parseInt(userBookings["hydra:totalItems"] / pageSize, 10)) {
      return;
    }

    setPage(page + value);
  };

  const decrementPage = (event) => {
    event.stopPropagation();

    event.preventDefault();

    addPage(-1);
  };

  const incrementPage = (event) => {
    event.stopPropagation();

    event.preventDefault();

    addPage(1);
  };

  return (
    <div className="App">
      <div className="container-fluid">
        <MainNavigation config={config} />
        <div className="app-wrapper">
          {deleteBooking && (
            <UserBookingDelete
              config={config}
              booking={deleteBooking}
              onBookingDeleted={onBookingDeleted}
              close={close}
            />
          )}
          {editBooking && (
            <UserBookingEdit config={config} booking={editBooking} onBookingChanged={onBookingChanged} close={close} />
          )}
          {!editBooking && !deleteBooking && (
            <div className="userpanel row">
              <div className="col no-padding">
                {loading && <LoadingSpinner />}

                {!loading && !editBooking && (
                  <div style={{ marginBottom: "1em" }}>
                    <form onSubmit={submitSearch}>
                      <input
                        value={search}
                        className="filter"
                        style={{ marginRight: "1em" }}
                        placeholder="Søgetekst"
                        name="filterText"
                        onChange={onFilterChange}
                        type="text"
                      />
                      <button type="submit">Søg</button>
                    </form>
                  </div>
                )}

                {!loading && !editBooking && userBookings && (
                  <>
                    {false && (
                      <div className="userbookings-sorting-container">
                        {renderSortingButton("displayName", "Lokale/Resurse")}
                        {renderSortingButton("start", "Dato")}
                        {renderSortingButton("subject", "Titel")}
                      </div>
                    )}
                    <div className="userbookings-container">
                      {sortBookings(currentBookings, sortField, sortDirection).map(renderBooking)}
                    </div>
                  </>
                )}

                {userBookings && (
                  <div style={{ display: "flex", justifyContent: "space-between" }}>
                    <div>
                      <button type="button" onClick={decrementPage} style={{ margin: "1em" }}>
                        ←
                      </button>
                      Side {page + 1} / {parseInt(userBookings["hydra:totalItems"] / pageSize, 10) + 1}
                      <button type="button" onClick={incrementPage} style={{ margin: "1em" }}>
                        →
                      </button>
                    </div>

                    <div style={{ margin: "1em" }}>Antal bookinger: {userBookings["hydra:totalItems"]}</div>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

UserPanel.propTypes = {
  config: PropTypes.shape({
    api_endpoint: PropTypes.string.isRequired,
  }).isRequired,
};

export default UserPanel;
