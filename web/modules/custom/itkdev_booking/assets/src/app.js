import React, { useEffect, useState } from "react";
import { ToastContainer } from "react-toastify";
import "dayjs/locale/da";
import dayjs from "dayjs";
import ConfigLoader from "./util/config-loader";
import CreateBooking from "./create-booking";
import UserPanel from "./user-panel";
import SystemFailureScreen from "./system-failure-screen";
import "./app.scss";

// Set day js locale globally.
dayjs.locale("da");

/**
 * App component.
 *
 * @returns {JSX.Element} App component.
 */
function App() {
  // App configuration and behavior.
  const [config, setConfig] = useState(null);
  const [loadingConfig, setLoadingConfig] = useState(true);

  // Get configuration.
  useEffect(() => {
    ConfigLoader.loadConfig()
      .then((loadedConfig) => {
        setConfig(loadedConfig);
      })
      .finally(() => {
        setLoadingConfig(false);
      });
  }, []);

  return (
    <>
      <ToastContainer position="bottom-right" autoClose={5000} />

      {config && (
        <>
          {config.create_booking_mode && <CreateBooking config={config} />}
          {!config.create_booking_mode && <UserPanel config={config} />}
        </>
      )}

      {!config && !loadingConfig && <SystemFailureScreen />}
    </>
  );
}

export default App;
