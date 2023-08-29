import React, { useEffect, useState } from "react";
import Select, { createFilter } from "react-select";
import * as PropTypes from "prop-types";
import "react-toastify/dist/ReactToastify.css";
import { capacityOptions, facilityOptions } from "../util/filter-utils";
import { setAriaLabelFilters } from "../util/dom-manipulation-utils";
import { hasOwnProperty, sortOptionsBy } from "../util/helpers";
import "./create-booking-filters.scss";

/**
 * CreateBooking component.
 *
 * @param {object} props The props
 * @param {object} props.filterParams Filter parameters.
 * @param {Function} props.setFilterParams Set filter parameters.
 * @param {Array} props.allResources All resources.
 * @param {boolean} props.disabled Disable filters.
 * @param {string} props.userType User type: citizen or businessPartner.
 * @param {Array} props.locationFilter Location filters.
 * @param {Function} props.setLocationFilter Set Location filters.
 * @param {Array} props.resourceFilter Resource filters.
 * @param {Function} props.setResourceFilter Set resource filters.
 * @param {string} props.resourceCategoryFilter Resource category filters.
 * @param {Function} props.setResourceCategoryFilter Set resource category filters.
 * @returns {JSX.Element} Component.
 */
function CreateBookingFilters({
  filterParams,
  setFilterParams,
  allResources,
  disabled,
  userType,
  locationFilter,
  setLocationFilter,
  resourceFilter,
  setResourceFilter,
  resourceCategoryFilter,
  setResourceCategoryFilter,
}) {
  const [capacityFilter, setCapacityFilter] = useState([]);
  const [facilityFilter, setFacilityFilter] = useState([]);
  const [hasWhitelist, setHasWhitelist] = useState(false);
  const [locationOptions, setLocationOptions] = useState([]);
  const [resourcesOptions, setResourcesOptions] = useState([]);
  const [resourceCategoryOptions, setResourceCategoryOptions] = useState([]);

  // TODO: Describe.
  setAriaLabelFilters();

  // Loop all resources and set filter options
  useEffect(() => {
    if (resourcesOptions.length === allResources.length) {
      return;
    }

    const locations = [];

    allResources.forEach((item) => {
      if (item.location !== "") {
        if (locations.findIndex((e) => e.value === item.location) === -1) {
          locations.push({
            value: item.location,
            label: item.locationDisplayName ?? item.location,
          });
        }
      }
    });

    setLocationOptions(sortOptionsBy(locations, "label"));

    setResourcesOptions(
      sortOptionsBy(allResources, "resourceDisplayName").map((value) => {
        return {
          value: value.resourceMail,
          label: value.resourceDisplayName ?? value.resourceName,
        };
      })
    );

    const newResourceCategoryOptions = [
      ...new Set(
        allResources
          .filter((resource) => resource?.resourceCategory !== null && resource?.resourceCategory !== "")
          .map((resource) => resource.resourceCategory)
      ),
    ];

    // Make sure "Lokale" is in the array, since it is default for resources without resourceCategory.
    if (newResourceCategoryOptions.indexOf("Lokale") === -1) {
      newResourceCategoryOptions.push("Lokale");
    }

    setResourceCategoryOptions(newResourceCategoryOptions);
  }, [allResources]);

  // Set location filter and resource dropdown options.
  useEffect(() => {
    const locationValues = locationFilter.map(({ value }) => value);

    setFilterParams({ ...filterParams, ...{ "location[]": locationValues } });
  }, [locationFilter]);

  // Set resource filter.
  useEffect(() => {
    const resourceValues = resourceFilter.map(({ value }) => value);

    setFilterParams({
      ...filterParams,
      ...{ "resourceMail[]": resourceValues },
    });
  }, [resourceFilter]);

  // Set only whitelisted filter.
  useEffect(() => {
    if (hasWhitelist) {
      setFilterParams({
        ...filterParams,
        ...{ hasWhitelist },
      });
    } else if (hasOwnProperty(filterParams, "hasWhitelist")) {
      const newFilterParams = { ...filterParams };

      delete newFilterParams.hasWhitelist;

      setFilterParams(newFilterParams);
    }
  }, [hasWhitelist]);

  // Set capacity filter.
  useEffect(() => {
    const newFilterParams = { ...filterParams };
    const capacityType = capacityFilter.type ?? null;
    const capacityValue = capacityFilter.value ?? 0;

    // Delete opposite entry to prevent both capacity[between] and capacity[gt] being set, causing a dead end.
    delete newFilterParams["capacity[between]"];

    delete newFilterParams["capacity[gt]"];

    // Set capacity filter according to capacityType.
    let capacity;

    switch (capacityType) {
      case "between":
        capacity = { "capacity[between]": capacityValue };

        break;
      case "gt":
        capacity = { "capacity[gt]": capacityValue };

        break;
      default:
        break;
    }

    setFilterParams({ ...newFilterParams, ...capacity });
  }, [capacityFilter]);

  // Set facility filter.
  useEffect(() => {
    const filterParamsObj = { ...filterParams };

    delete filterParamsObj.monitorEquipment;

    delete filterParamsObj.wheelchairAccessible;

    delete filterParamsObj.videoConferenceEquipment;

    delete filterParamsObj.catering;

    const facilitiesObj = {};

    facilityFilter.forEach((value) => {
      facilitiesObj[value.value] = "true";
    });

    setFilterParams({ ...filterParamsObj, ...facilitiesObj });
  }, [facilityFilter]);

  // Set resource category filter.
  useEffect(() => {
    setFilterParams({ ...filterParams, resourceCategory: resourceCategoryFilter });
  }, [resourceCategoryFilter]);

  return (
    <>
      <div className="category-tabs">
        {resourceCategoryOptions.map((category, index) => (
          <div
            key={category}
            className={`category-tab ${resourceCategoryFilter === category ? "active" : ""} ${
              index === resourceCategoryOptions.length - 1 ? "last" : ""
            } ${index === 0 ? "first" : ""}`}
          >
            <button type="button" className="category-button" onClick={() => setResourceCategoryFilter(category)}>
              {category}
            </button>
          </div>
        ))}
      </div>
      <div className={`row filters-wrapper ${disabled ? "disable-wrapper" : ""}`}>
        <div className="col-md-3 col-xs-12 small-padding">
          <label htmlFor="location-filter">
            Filtrér på lokationer
            {/* Dropdown with locations */}
            <Select
              styles={{}}
              id="location-filter"
              className="filter"
              defaultValue={locationFilter}
              value={locationFilter}
              placeholder="Lokationer..."
              placeholderClassName="dropdown-placeholder"
              closeMenuOnSelect={false}
              options={locationOptions}
              onChange={(selectedLocations) => {
                setLocationFilter(selectedLocations);
              }}
              isLoading={Object.values(locationOptions).length === 0}
              loadingMessage={() => "Henter lokationer.."}
              filterOption={createFilter({ ignoreAccents: false })} // Improved performance with large datasets
              isMulti
            />
          </label>
        </div>
        <div className="col-md-3 col-xs-12 small-padding">
          <label htmlFor="resource-filter">
            Filtrér på lokaler/ressourcer
            {/* Dropdown with resources */}
            <Select
              styles={{}}
              id="resource-filter"
              className="filter"
              defaultValue={resourceFilter}
              placeholder="Ressourcer..."
              placeholderClassName="dropdown-placeholder"
              closeMenuOnSelect={false}
              options={resourcesOptions}
              onChange={(selectedResources) => {
                setResourceFilter(selectedResources);
              }}
              isLoading={Object.values(resourcesOptions).length === 0}
              loadingMessage={() => "Henter ressourcer.."}
              filterOption={createFilter({ ignoreAccents: false })} // Improved performance with large datasets
              isMulti
            />
          </label>
        </div>
        <div className="col-md-3 col-xs-12 small-padding">
          <label htmlFor="facility-filter">
            Filtrér på faciliteter
            {/* Dropdown with facilities */}
            <Select
              styles={{}}
              id="facility-filter"
              className="filter"
              defaultValue={facilityFilter}
              placeholder="Facilitieter..."
              placeholderClassName="dropdown-placeholder"
              closeMenuOnSelect={false}
              options={facilityOptions}
              onChange={(selectedFacilities) => {
                setFacilityFilter(selectedFacilities);
              }}
              isMulti
            />
          </label>
        </div>
        <div className="col-md-3 col-xs-12 small-padding">
          <label htmlFor="capacity-filter">
            Filtrér på kapacitet
            {/* Dropdown with capacity */}
            <Select
              styles={{}}
              id="capacity-filter"
              className="filter"
              defaultValue={{ value: "0", label: "Alle", type: "gt" }}
              placeholder="Siddepladser..."
              placeholderClassName="dropdown-placeholder"
              closeMenuOnSelect
              options={capacityOptions}
              onChange={(selectedCapacity) => {
                setCapacityFilter(selectedCapacity);
              }}
            />
          </label>
        </div>
        {userType === "businessPartner" && (
          <div className="col-md-3 col-xs-12 small-padding">
            <label htmlFor="capacity-filter" style={{ display: "flex" }}>
              <input
                type="checkbox"
                value={hasWhitelist}
                style={{ width: "20px", height: "20px" }}
                onChange={({ target }) => {
                  setHasWhitelist(!!target.checked);
                }}
              />
              <span style={{ marginLeft: "5px" }}>Mine udvalgte ressourcer</span>
            </label>
          </div>
        )}
      </div>
    </>
  );
}

CreateBookingFilters.defaultProps = {
  userType: "",
};

CreateBookingFilters.propTypes = {
  filterParams: PropTypes.shape({}).isRequired,
  setFilterParams: PropTypes.func.isRequired,
  allResources: PropTypes.arrayOf(PropTypes.shape({})).isRequired,
  disabled: PropTypes.bool.isRequired,
  userType: PropTypes.string,
  locationFilter: PropTypes.arrayOf(PropTypes.shape({})).isRequired,
  setLocationFilter: PropTypes.func.isRequired,
  resourceFilter: PropTypes.arrayOf(PropTypes.shape({})).isRequired,
  setResourceFilter: PropTypes.func.isRequired,
  resourceCategoryFilter: PropTypes.string.isRequired,
  setResourceCategoryFilter: PropTypes.func.isRequired,
};

export default CreateBookingFilters;
