/** Remove empty aria-labelledby from empty selector. */
export function removeEmptyAriaLabelled() {
  const selections = document.querySelectorAll(".fc > .fc-view-harness");

  selections.forEach((selection) => {
    if (!selection.getAttribute("aria-labelledby")) {
      selection.removeAttribute("aria-labelledby");
    }
  });
}

/** Remove tab index on fullcalendar scroller index. */
export function tabindexCalendar() {
  // Set table scroller to not be tab indexed.
  const scrollers = document.querySelectorAll(".fc-scroller");

  scrollers.forEach((selection) => {
    selection.setAttribute("tabindex", "-1");
  });
}

/** Make filters accessible. */
export function setAriaLabelFilters() {
  const filters = document.querySelectorAll(".filters-wrapper .filter");

  filters.forEach((filter) => {
    const id = filter.getAttribute("id");
    const inputs = document.querySelectorAll(`#${id} input`);

    inputs.forEach((input) => {
      input.setAttribute("aria-label", id);

      input.setAttribute("aria-controls", id);
    });
  });
}
