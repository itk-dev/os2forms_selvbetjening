// react
import React, { useState, useEffect, useRef } from "react";
import * as PropTypes from "prop-types";
// openlayers
import Map from "ol/Map";
import View from "ol/View";
import TileLayer from "ol/layer/Tile";
import VectorLayer from "ol/layer/Vector";
import Feature from "ol/Feature";
import Point from "ol/geom/Point";
import { Icon, Style, Circle as CircleStyle, Fill, Text } from "ol/style";
import Overlay from "ol/Overlay";
import { Cluster } from "ol/source";
import VectorSource from "ol/source/Vector";
import TileWMS from "ol/source/TileWMS";
import Projection from "ol/proj/Projection";
import Proj4 from "proj4";
import { register } from "ol/proj/proj4";
import { boundingExtent, getHeight, getWidth } from "ol/extent";
import { getFeatures } from "../util/map-utils";
import "./map-wrapper.scss";

/**
 * MapWrapper component
 *
 * @param {object} props Props.
 * @param {object} props.resources Filtered resources array
 * @param {object} props.allResources All resources array
 * @param {object} props.config Config
 * @param {object} props.setLocationFilter Setter for location filter
 * @param {object} props.setResourceFilter Setter for resource filter
 * @param {string} props.setBookingView Setter for booking view
 * @param {boolean} props.useLocations Whether to render locations or resources
 * @returns {JSX.Element} MapWrapper component
 */
function MapWrapper({
  resources,
  allResources,
  config,
  setLocationFilter,
  setResourceFilter,
  setBookingView,
  useLocations,
  setFacilityFilter,
  filterParams
}) {
  const [map, setMap] = useState();
  const [vectorLayer, setVectorLayer] = useState(null);
  const [mapData, setMapData] = useState(null);
  const mapElement = useRef();

  let tooltip = useRef();

  useEffect(() => {
    if (!useLocations) {
      if (Object.keys(filterParams).length === 0) {
        setMapData(getFeatures(allResources, useLocations));
      } else {
        const features = getFeatures(resources, useLocations);

        setMapData(features);

        tooltip.innerHTML = "";
      }
    } else {
      setMapData(getFeatures(allResources, useLocations));
    }
  }, [resources, allResources]);

  useEffect(() => {
    if (map) {
      map
        .getLayers()
        .getArray()
        .forEach((value) => {
          // Loop vectorLayers and all but the original
          // eslint-disable-next-line no-underscore-dangle
          if (value.revision_ > 1) {
            map.removeLayer(value);
          }
        });
    }
  }, [mapData]);

  useEffect(() => {
    if (!mapData) {
      return;
    }

    // Styling of the map marker
    const iconStyle = new Style({
      image: new Icon({
        anchor: [0.55, 25],
        anchorXUnits: "fraction",
        anchorYUnits: "pixels",
        src: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABUAAAAdCAMAAABymtfJAAAAilBMVEVHcEwMbv0Mbv0Nbv0Mbv0eeP0Tcv0Mbv8Mbv0Mb/4Nbv0Nbv0Nbv0Nbv0Mbv0Nbv0Nbv2w0P8Nbv4Mbv0Nbv0Ma/sNbv1SmP4Lb/4Mbf2PvP41h/3b6v8whP6Etv4Nbv0Nbv0Nbv0Nbv0Nbv0Nbv2exf96r/7R5P8lfP0Nbv3////a6f/4+//8/f8k+JezAAAAKXRSTlMA/vHoQf3+CIYOi7vFNPjN2vopS+ACYfocGfz98ujks1OVd2mx2/np8fwU+MsAAAECSURBVCjPbZLpdsIgEIVngCQEsi/G2NbdVlDf//UMi9lO7x/CdyYXZi4ARjHjCc1owlkMo9ITRVRKIdJT6llb52pSXreW1oWaq6gNrBK1VF4BREKtJSJIG/eJGIaeNimc0cGffbn78gVnkOjgn9b60TkqwRpgeBugfpXOpIHMrsG3ofoR2B0B8i/NrW+4t3RnHTAH4U67l8/Xs3SXQAHMWWDQ/Xbuf0UY9FffBI69XXuADVk1TDbDdLZyReXWDG1VbEuHeA4LevAhMTqDlPmIIj6djzz6pDmLI6mmkI8fD3qc4BCTG10mohmFXs6uOomZ+Au2hNBeiCKXdkUh5gUfH9kbVG84zlaEEvsAAAAASUVORK5CYII=",
      }),
    });

    // Define feature array and apply styling
    const features = [];

    Object.values(mapData).forEach((value) => {
      const feature = new Feature({
        geometry: new Point([value.northing, value.easting]),
        resourceId: value.id,
        resourceName: value.resourceDisplayName ?? value.resourceName,
        resourceMail: value.resourceMail,
        location: value.location,
        locationName: value.locationDisplayName,
        children: value.resource_count,
      });

      feature.setStyle(iconStyle);

      features.push(feature);
    });

    const clusterSource = new Cluster({
      distance: 60,
      minDistance: 20,
      source: new VectorSource({
        features,
      }),
    });

    const styleCache = {};

    const clusterStyle = (feature) => {
      const size = feature.get("features").length;

      let style = styleCache[size];

      if (size === 1) {
        return iconStyle;
      }
      if (!style) {
        style = new Style({
          image: new CircleStyle({
            radius: 12,
            fill: new Fill({
              color: "#0d6efd",
            }),
          }),
          text: new Text({
            scale: 1.5,
            text: size.toString(),
            fill: new Fill({
              color: "#fff",
            }),
          }),
        });

        styleCache[size] = style;
      }

      return style;
    };

    const clusters = new VectorLayer({
      source: clusterSource,
      style: clusterStyle,
    });

    if (map) {
      setVectorLayer(clusters);
    }
  }, [mapData, map]);

  useEffect(() => {
    // Handles removing old layers and adding new ones
    if (map && vectorLayer) {
      map
        .getLayers()
        .getArray()
        .forEach((value) => {
          // eslint-disable-next-line no-underscore-dangle
          if (value.revision_ > 1) {
            map.removeLayer(value);
          }
        });

      map.addLayer(vectorLayer); // Add newly defined vectorLayer
    }
  }, [vectorLayer, map]);

  useEffect(() => {
    if (!config || config.df_map_username === "" || config.df_map_password === "") {
      return;
    }
    // Current map instances
    const mapChildren = mapElement.current.children;

    let mapAlreadyLoaded = false;

    // Check if map is already loaded
    Object.values(mapChildren).forEach((mapChild) => {
      if (mapChild.className.indexOf("ol-viewport") !== -1) {
        mapAlreadyLoaded = true;
      }
    });

    // Return if map is already loaded
    if (mapAlreadyLoaded) {
      return;
    }

    // Initial setup of map - this only runs once
    tooltip = document.getElementById("tooltip");

    // Proj4 projection definition
    Proj4.defs("EPSG:25832", "+proj=utm +zone=32 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs +type=crs");

    register(Proj4);

    // Projection settings for Denmark
    const dkprojection = new Projection({
      code: "EPSG:25832",
      extent: [-1877994.66, 3638086.74, 3473041.38, 9494203.2],
    });

    // Layer definition
    const layers = [
      new TileLayer({
        // Map tiles
        title: "WMS skærmkort (DAF)",
        type: "base",
        visible: true,
        preload: Infinity,
        source: new TileWMS({
          url: `https://services.datafordeler.dk/Dkskaermkort/topo_skaermkort/1.0.0/wms?username=${config.df_map_username}&password=${config.df_map_password}`,
          params: {
            LAYERS: "dtk_skaermkort",
            VERSION: "1.1.1",
            TRANSPARENT: "FALSE",
            FORMAT: "image/png",
          },
          attributions: '<p>Kort fra <a href="https://datafordeler.dk" target="_blank">Datafordeleren</a>.',
        }),
      }),
    ];

    // Map definition
    const initialMap = new Map({
      layers,
      target: mapElement.current,
      view: new View({
        minZoom: 2,
        maxZoom: 11,
        center: [574969.6851, 6223950.2116],
        zoom: 5.2,
        resolutions: [1638.4, 819.2, 409.6, 204.8, 102.4, 51.2, 25.6, 12.8, 6.4, 3.2, 1.6, 0.8, 0.4, 0.2, 0.1],
        projection: dkprojection,
      }),
    });

    // Tooltip definition
    const overlay = new Overlay({
      element: tooltip,
      offset: [0, -30],
      positioning: "bottom-center",
    });

    initialMap.addOverlay(overlay);

    // display popup on click
    initialMap.on("click", (evt) => {
      const { pixel } = evt;

      const targetFeature = initialMap.forEachFeatureAtPixel(pixel, (target) => {
        return target;
      });

      if (targetFeature) {
        const isCluster = targetFeature.values_.features.length > 1;

        // Clicked feature is a cluster. Zoom in on it.
        if (isCluster) {
          const { features } = targetFeature.values_;
          const extent = boundingExtent(features.map((r) => r.getGeometry().getCoordinates()));
          const mapZoom = initialMap.getView().getZoom();
          const mapMaxZoom = initialMap.getView().getMaxZoom();
          const resolution = initialMap.getView().getResolution();

          initialMap.getView().fit(extent, { duration: 1500, padding: [250, 100, 100, 100] });

          if (mapZoom === mapMaxZoom && getWidth(extent) < resolution && getHeight(extent) < resolution) {
            // Clicked feature is a single. Show info.
            tooltip.style.display = targetFeature ? "" : "none";

            const coordinates = targetFeature.values_.geometry.flatCoordinates;

            // eslint-disable-next-line no-underscore-dangle
            overlay.setPosition(coordinates);

            const count = targetFeature.values_.features.length;
            const dataResourceMailArray = targetFeature.values_.features.map((feature) => feature.values_.resourceMail);
            const dataResourceNameArray = targetFeature.values_.features.map((feature) => feature.values_.resourceName);

            const locationName =
              targetFeature.values_.features[0].values_.locationName ??
              targetFeature.values_.features[0].values_.location ??
              "";

            // eslint-disable-next-line no-underscore-dangle
            tooltip.innerHTML = `<div class='tooltip-closer'>✖️</div>
            <div class='tooltip-text'>
                <span><b>${locationName}</b></span><span>${count} ressourcer</span>
                <a data-resource-mail="${dataResourceMailArray}"
                  data-resource-name="${dataResourceNameArray}"
                  class='tooltip-btn'">
                  Vis i kalender
                </a>
            </div>`;
          }
        } else {
          // Clicked feature is a single. Show info.
          tooltip.style.display = targetFeature ? "" : "none";

          const coordinates = targetFeature.values_.geometry.flatCoordinates;

          // eslint-disable-next-line no-underscore-dangle
          overlay.setPosition(coordinates);

          if (useLocations) {
            const location =
              targetFeature.values_.features[0].values_.location ??
              targetFeature.values_.features[0].values_.locationName ??
              "";

            const locationName =
              targetFeature.values_.features[0].values_.locationName ??
              targetFeature.values_.features[0].values_.location ??
              "";

            const children = targetFeature.values_.features[0].values_.children ?? "";

            // eslint-disable-next-line no-underscore-dangle
            tooltip.innerHTML = `<div class='tooltip-closer'>✖️</div>
              <div class='tooltip-text'>
                  <span><b>${locationName}</b></span><span>${children} test</span>
                  <a data-location="${location}"
                    data-location-name="${locationName}"
                    class='tooltip-btn'">
                    Vis i kalender
                  </a>
              </div>`;
          } else {
            const name = targetFeature.values_.features[0].values_.resourceName ?? "";
            const resourceMail = targetFeature.values_.features[0].values_.resourceMail ?? "";

            // eslint-disable-next-line no-underscore-dangle
            tooltip.innerHTML = `<div class='tooltip-closer'>✖️</div>
              <div class='tooltip-text'>
                  <span><b>${name}</b></span>
                  <a data-resource-mail="${resourceMail}"
                    data-resource-name="${name}"
                    class='tooltip-btn'">
                    Vis i kalender
                  </a>
              </div>`;
          }
        }
      }
    });

    // change mouse cursor when over marker
    initialMap.on("pointermove", (e) => {
      const pixel = initialMap.getEventPixel(e.originalEvent);
      const hit = initialMap.hasFeatureAtPixel(pixel);

      initialMap.getTarget().style.cursor = hit ? "pointer" : "";
    });

    // Tooltip closer click event
    document.getElementById("tooltip").addEventListener("click", (event) => {
      const target = event.target.className;

      if (target === "tooltip-closer") {
        tooltip.innerHTML = "";
      }
      if (target === "tooltip-btn") {
        if (useLocations) {
          setLocationFilter([
            {
              value: event.target.getAttribute("data-location"),
              label: event.target.getAttribute("data-location-name"),
            },
          ]);
        } else {
          const dataResourceMail = event.target.getAttribute("data-resource-mail");
          const dataResourceName = event.target.getAttribute("data-resource-name");

          if (dataResourceMail.indexOf(",") > -1 && dataResourceName.indexOf(",") > -1) {
            const dataResourceMailArray = dataResourceMail.split(",");
            const dataResourceNameArray = dataResourceName.split(",");

            if (dataResourceMailArray.length === dataResourceNameArray.length) {
              // Merge arrays into array of objects
              const mergedArray = dataResourceMailArray.map((value, index) => ({
                value,
                label: dataResourceNameArray[index],
            }));
            setFacilityFilter([]);

            //Settimeout to prevent filters being set at the same time.
            setTimeout(() => {
              setResourceFilter(mergedArray);
            }, 50)
            }
          } else {
            setResourceFilter([
              {
                value: event.target.getAttribute("data-resource-mail"),
                label: event.target.getAttribute("data-resource-name"),
              },
            ]);
          }
        }

        setBookingView("calendar");
      }
    });

    // save map and vector layer references to state
    setMap(initialMap);
  }, []);

  return (
    <div className="map-container">
      <div ref={mapElement} className="map" id="map">
        <div id="tooltip" className="tooltip" />
      </div>
    </div>
  );
}

MapWrapper.propTypes = {
  resources: PropTypes.arrayOf(PropTypes.shape({})),
  allResources: PropTypes.arrayOf(PropTypes.shape({})),
  config: PropTypes.shape({
    df_map_username: PropTypes.string.isRequired,
    df_map_password: PropTypes.string.isRequired,
  }).isRequired,
  setLocationFilter: PropTypes.func.isRequired,
  setResourceFilter: PropTypes.func.isRequired,
  setBookingView: PropTypes.func.isRequired,
  useLocations: PropTypes.bool.isRequired,
};

MapWrapper.defaultProps = {
  resources: {},
  allResources: {},
};

export default MapWrapper;
