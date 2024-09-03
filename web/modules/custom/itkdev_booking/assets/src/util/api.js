import dayjs from "dayjs";

export default class Api {
  static async fetchAllResources(apiEndpoint) {
    return fetch(`${apiEndpoint}itkdev_booking/resources-all`).then((response) => {
      if (!response.ok) {
        throw new Error(`This is an HTTP error: The status is ${response.status}`);
      }

      return response.json();
    });
  }

  static async fetchLocations(apiEndpoint) {
    return fetch(`${apiEndpoint}itkdev_booking/locations`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`This is an HTTP error: The status is ${response.status}`);
        }

        return response.json();
      })
      .then((data) => data["hydra:member"]);
  }

  static async fetchResources(apiEndpoint, urlSearchParams) {
    return fetch(`${apiEndpoint}itkdev_booking/resources?${urlSearchParams}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`This is an HTTP error: The status is ${response.status}`);
        }

        return response.json();
      })
      .then((data) => data["hydra:member"]);
  }

  static async fetchEvents(apiEndpoint, resources, date) {
    const dateEnd = dayjs(date).endOf("day");

    // Setup query parameters.
    const urlSearchParams = new URLSearchParams({
      resources: resources.map((resource) => resource.resourceMail),
      dateStart: date.toISOString(),
      dateEnd: dateEnd.toISOString(),
      page: 1,
    });

    // Events on resource.
    return fetch(`${apiEndpoint}itkdev_booking/busy_intervals?${urlSearchParams}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`This is an HTTP error: The status is ${response.status}`);
        }

        return response.json();
      })
      .then((data) => data["hydra:member"]);
  }

  static async fetchResource(apiEndpoint, resourceEmail) {
    return fetch(`${apiEndpoint}itkdev_booking/resources/${resourceEmail}`).then((response) => {
      if (!response.ok) {
        throw new Error(`This is an HTTP error: The status is ${response.status}`);
      }

      return response.json();
    });
  }

  static async fetchUserBookings(apiEndpoint, search, sort, page, pageSize) {
    const params = new URLSearchParams({
      page,
      pageSize,
      'start[after]': new Date().toISOString(),
    });

    params.append("title", search);

    params.append(Object.keys(sort)[0], Object.values(sort)[0]);

    return fetch(`${apiEndpoint}itkdev_booking/user-bookings?${params}`).then((response) => {
      if (!response.ok) {
        throw new Error(`This is an HTTP error: The status is ${response.status}`);
      }

      return response.json();
    });
  }

  static async deleteBooking(apiEndpoint, bookingId) {
    return fetch(`${apiEndpoint}itkdev_booking/user-booking/${bookingId}`, {
      method: "DELETE",
    }).then((response) => {
      if (!response.ok) {
        throw new Error(`This is an HTTP error: The status is ${response.status}`);
      }
    });
  }

  static async patchBooking(apiEndpoint, bookingId, newData) {
    return fetch(`${apiEndpoint}itkdev_booking/user-booking/${bookingId}`, {
      method: "PATCH",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify(newData),
    }).then((response) => {
      if (!response.ok) {
        throw new Error(`This is an HTTP error: The status is ${response.status}`);
      }

      return response.json();
    });
  }

  static async fetchUserInformation(apiEndpoint) {
    return fetch(`${apiEndpoint}itkdev_booking/user-information`).then((response) => {
      if (!response.ok) {
        throw new Error(`This is an HTTP error: The status is ${response.status}`);
      }

      return response.json();
    });
  }

  static fetchBookingStatus(apiEndpoint, pendingBookings) {
    return fetch(`${apiEndpoint}itkdev_booking/pending-bookings`, {
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      method: "POST",
      body: JSON.stringify({
        ids: pendingBookings,
      }),
    }).then((response) => {
      if (!response.ok) {
        throw new Error(`This is an HTTP error: The status is ${response.status}`);
      }

      return response.json();
    });
  }
}
