import React from "react";

/**
 * System failure component
 *
 * @returns {JSX.Element} System failure component.
 */
function SystemFailureScreen() {
  return (
    <div className="App">
      <div className="container-fluid">
        <div className="app-wrapper">
          <div className="row no-gutter main-container">
            <div className="col-md-12">
              <div className="row">
                <div className="col-xs-offset-3 col-xs-6">
                  <h1>System fejl</h1>
                  <div>Book aarhus servicen er desværre utilgængelig i øjeblikket. Prøv igen senere...</div>
                  <div>Vi beklager ulejligheden.</div>
                  <div>
                    <button
                      type="button"
                      className="button button-primary"
                      onClick={() => window.location.reload(false)}
                    >
                      Genindlæs siden
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default SystemFailureScreen;
