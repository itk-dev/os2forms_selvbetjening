import React from "react";
import * as dayjs from "dayjs";
import * as PropTypes from "prop-types";
import Select from "react-select";
import { calendarTimeSelect } from "../util/filter-utils";
import "./calendar-header.scss";

/**
 * Calendar header component.
 *
 * @param {object} props Props.
 * @param {object} props.date Date.
 * @param {Function} props.setDate Set date function.
 * @param {Function} props.setIsLoading Loading state setter.
 * @param {Function} props.setTimeScroll Timescroll state setter.
 * @param {string} props.scrollTime Seleted scrollTime
 * @returns {JSX.Element} Calendar header component.
 */
function CalendarHeader({ date, setDate, setIsLoading, setTimeScroll, scrollTime }) {
  const onChangeDate = (event) => {
    switch (event.target.id) {
      case "calendar-today":
        setDate(new Date());

        break;
      case "calendar-back":
        if (new Date() < date) {
          setDate(new Date(dayjs(date).subtract(1, "day").format("YYYY-MM-DD")));

          setIsLoading(true);
        }

        break;
      case "calendar-forward":
        setDate(new Date(dayjs(date).add(1, "day").format("YYYY-MM-DD")));

        setIsLoading(true);

        break;
      case "calendar-datepicker":
        setDate(new Date(event.target.value));

        break;
      default:
    }
  };

  const getScrollTimeObj = () => {
    if (!scrollTime) {
      return false;
    }

    return { value: scrollTime, label: scrollTime };
  };

  return (
    <div className="row">
      <div className="col no-gutter">
        <div className="row calendar-header-wrapper">
          <div className="col-md-2 col-sm-6 col-xs-6 small-padding">
            <button id="calendar-today" className="booking-btn" type="button" onClick={onChangeDate}>
              I dag
            </button>
          </div>
          <div className="calendar-hidden-lg col-sm-6 col-xs-6 small-padding">
            <div className="calendar-nav">
              <button
                id="calendar-back"
                className="booking-btn"
                type="button"
                disabled={new Date() > date}
                onClick={onChangeDate}
              >
                ‹
              </button>
              <button id="calendar-forward" className="booking-btn" type="button" onClick={onChangeDate}>
                ›
              </button>
            </div>
          </div>
          <div className="col-md-8 col-sm-12 col-xs-12 small-padding datepicker-container">
            <div className="datepicker">
              <span>Vælg dato</span>
              <label htmlFor="calendar-datepicker" className="datepicker-label">
                <span hidden>Dato</span>
                <input
                  type="date"
                  id="calendar-datepicker"
                  min={dayjs(new Date()).format("YYYY-MM-DD")}
                  value={dayjs(date).format("YYYY-MM-DD")}
                  onChange={onChangeDate}
                />
                <button type="button" id="calendar_text">
                  <span>{dayjs(date).format("D. MMMM YYYY")}</span>{" "}
                  <div>
                    <span>📅</span>
                  </div>
                </button>
              </label>
            </div>
            <div className="timepicker">
              <span>Vælg start-tidspunkt</span>
              <Select
                id="calendar-hours-filter"
                className="filter"
                defaultValue={getScrollTimeObj()}
                placeholder="Tid..."
                placeholderClassName="dropdown-placeholder"
                closeMenuOnSelect
                options={calendarTimeSelect}
                onChange={(selectedHour) => {
                  setTimeScroll(selectedHour);
                }}
                loadingMessage={() => "Henter tider.."}
                isSearchable={false}
                menuPlacement="bottom"
                menuPortalTarget={document.body}
                styles={{ menuPortal: (base) => ({ ...base, zIndex: 9999 }) }}
              />
            </div>
          </div>
          <div className="col-md-2 calendar-hidden-sm small-padding">
            <div className="calendar-nav">
              <button
                id="calendar-back"
                className="booking-btn"
                type="button"
                disabled={new Date() > date}
                onClick={onChangeDate}
              >
                ‹
              </button>
              <button id="calendar-forward" className="booking-btn" type="button" onClick={onChangeDate}>
                ›
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

CalendarHeader.propTypes = {
  date: PropTypes.shape({}).isRequired,
  setDate: PropTypes.func.isRequired,
  setIsLoading: PropTypes.func.isRequired,
  setTimeScroll: PropTypes.func.isRequired,
  scrollTime: PropTypes.string.isRequired,
};

export default CalendarHeader;
