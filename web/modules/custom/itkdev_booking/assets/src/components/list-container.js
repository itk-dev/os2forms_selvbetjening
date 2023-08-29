import React, { useEffect } from "react";
import * as PropTypes from "prop-types";
import List from "./list";
import NoResultOverlay from "./no-result-overlay";
import LoadingSpinner from "./loading-spinner";
import "./list-container.scss";

/**
 * @param {object} props Props.
 * @param {object} props.resources Resources object
 * @param {object} props.setShowResourceDetails Resource object to show details for
 * @param {boolean} props.userHasInteracted Has the user interacted with filters
 * @param {boolean} props.isLoading Loading state.
 * @param {boolean} props.setIsLoading Set loading state.
 * @returns {JSX.Element} List element containing resources
 */
function ListContainer({ resources, setShowResourceDetails, userHasInteracted, isLoading, setIsLoading }) {
  useEffect(() => {
    setIsLoading(false);
  }, [resources]);

  return (
    <div className={`List no-gutter col-md-12 ${isLoading === true ? "list-loading" : ""}`}>
      {isLoading && <LoadingSpinner />}
      {(!resources || (resources && resources.length === 0)) && !userHasInteracted && (
        <NoResultOverlay state="initial" />
      )}
      {(!resources || (resources && resources.length === 0)) && userHasInteracted && (
        <NoResultOverlay state="noresult" />
      )}
      <div className="row">
        <div className="col-md-12">
          {resources && <List resources={resources} setShowResourceDetails={setShowResourceDetails} />}
        </div>
      </div>
    </div>
  );
}

ListContainer.propTypes = {
  resources: PropTypes.arrayOf(PropTypes.shape({})),
  setShowResourceDetails: PropTypes.func.isRequired,
  userHasInteracted: PropTypes.bool,
  isLoading: PropTypes.bool.isRequired,
  setIsLoading: PropTypes.func.isRequired,
};

ListContainer.defaultProps = {
  resources: {},
  userHasInteracted: false,
};

export default ListContainer;
