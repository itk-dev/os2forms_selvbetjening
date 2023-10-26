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
import { boundingExtent } from "ol/extent";
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
}) {
  const [map, setMap] = useState();
  const [vectorLayer, setVectorLayer] = useState(null);
  const [mapData, setMapData] = useState(null);
  const mapElement = useRef();

  useEffect(() => {
    if (resources?.length > 0 && !useLocations) {
      const features = getFeatures(resources, useLocations);

      setMapData(features);
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

    // TODO: prevent map from loading new vectorlayer with same results

    // Styling of the map marker
    const iconStyle = new Style({
      image: new Icon({
        anchor: [0.5, 46],
        anchorXUnits: "fraction",
        anchorYUnits: "pixels",
        src: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABUAAAAdCAMAAABymtfJAAAAilBMVEVHcEwMbv0Mbv0Nbv0Mbv0eeP0Tcv0Mbv8Mbv0Mb/4Nbv0Nbv0Nbv0Nbv0Mbv0Nbv0Nbv2w0P8Nbv4Mbv0Nbv0Ma/sNbv1SmP4Lb/4Mbf2PvP41h/3b6v8whP6Etv4Nbv0Nbv0Nbv0Nbv0Nbv0Nbv2exf96r/7R5P8lfP0Nbv3////a6f/4+//8/f8k+JezAAAAKXRSTlMA/vHoQf3+CIYOi7vFNPjN2vopS+ACYfocGfz98ujks1OVd2mx2/np8fwU+MsAAAECSURBVCjPbZLpdsIgEIVngCQEsi/G2NbdVlDf//UMi9lO7x/CdyYXZi4ARjHjCc1owlkMo9ITRVRKIdJT6llb52pSXreW1oWaq6gNrBK1VF4BREKtJSJIG/eJGIaeNimc0cGffbn78gVnkOjgn9b60TkqwRpgeBugfpXOpIHMrsG3ofoR2B0B8i/NrW+4t3RnHTAH4U67l8/Xs3SXQAHMWWDQ/Xbuf0UY9FffBI69XXuADVk1TDbDdLZyReXWDG1VbEuHeA4LevAhMTqDlPmIIj6djzz6pDmLI6mmkI8fD3qc4BCTG10mohmFXs6uOomZ+Au2hNBeiCKXdkUh5gUfH9kbVG84zlaEEvsAAAAASUVORK5CYII=",
        // src: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABsAAAAlCAMAAACEemK6AAABXFBMVEUAAAAAgP8AZv8AgP8Abf8AYP8Acf8XdP8Vav8Udv8Lb/8KcP8PbP8Pcf8Ocf8Mbv8Lb/8NcP8Nb/8Nbf8Mb/8Mbf8MbvsLbfsPb/sObvsObPwNcPwNbvwMbfwObfwNbvwNbv0Mbv0Ob/0Obf0Mbf0Mbv0Mbv0Obv0Nbv0Nbf0Nbv0Nbv0Mb/0Mbv0Mbf0Obv0Ob/0Obv0Nb/0Nbf0Nbv0Nbv0Nbv0Nbv0Mbv0Mb/1ppv4Nbvwvgv4NbvwNbvwNb/y81/8Nb/2jyf4Nbf0kfP0Nbv0Nbv2PvP45if4Nbv0dd/0Nbv0Nbv0Nbv0Nbv0Nbv0Nbv1SmP4Nbv0Nbv08iv0Nb/0Nbv0Nbv32+f8Rcf3u9f8Mbv281/4Nbv2cxP4Nbv17sP4Nbv0Nbv0Xdf1foP4Nbv0Nbv0Nbv0Nbv07if1Umf5cnf6Nu/6x0P7H3f/t9P/u9f/2+f/4+//////ZrbbCAAAAaHRSTlMABAUGBwgJCwwNFxkhIiQsLjk8PT4/QURFSElQUVRZZGZrbG58fX+Ai4yNjpGSk5SWl5iam5ydoKSmv8PExcfLztLT1NTW2Nna3Nzd3t/l5+jo7O3v8PLz8/T09ff4+Pn5+vv7+/z9/kqOLf8AAAABYktHRHNBCT3OAAABKklEQVQYGW3BhVYCQQAF0CeoWBjYid2Nio2Fiig22IGCz475/3Nk2RlY2LkXSnGbPxC5jwT8rcXI5RqIUYn2u2DRFKFVpBEZ3kfmindB6qSdF2k1t7R7rEVK4QF1wk4APdTrBgqOqHdYgAZmbO3c3J0xox4jVBaF4YPKEFYoLQvTO6UlhCmtCdMXpRCuKV0I0y+la8QoXQnTD6UoNiltCNMnpXXMUtr7E4a/V0o+tFDZ/RFCfL9RaYbjlMrl8cPzE5UTBzBBvXEAFXHqJCqRskCdeRiqk7RLVCFtjnYzMJVFmS9WDqmP+XqhOIPMtV2EjLoXWiU9sPDRahpWJSFm7ZcihydJJVmPPMNUBpHPsUpTwAkb9zkN525otNPQAa0pkpPQKxwLjjqR9Q+OaxIua1fKFgAAAABJRU5ErkJggg==",
      }),
    });

    // Define feature array and apply styling
    const features = [];

    Object.values(mapData).forEach((value) => {
      const feature = new Feature({
        geometry: new Point([value.northing, value.easting]),
        name: value.locationDisplayName ?? value.location ?? value.resourceName,
        locationId: value.locationId ?? value.id,
        resourceMail: value.resourceMail,
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
    const tooltip = document.getElementById("tooltip");

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
        maxZoom: 13,
        center: [574969.6851, 6223950.2116],
        zoom: 5.2,
        resolutions: [1638.4, 819.2, 409.6, 204.8, 102.4, 51.2, 25.6, 12.8, 6.4, 3.2, 1.6, 0.8, 0.4, 0.2, 0.1],
        projection: dkprojection,
      }),
    });

    // Tooltip definition
    const overlay = new Overlay({
      element: tooltip,
      offset: [0, -55],
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

          initialMap.getView().fit(extent, { duration: 1500, padding: [250, 100, 100, 100] });
        } else {
          // Clicked feature is a single. Show info.
          tooltip.style.display = targetFeature ? "" : "none";

          const coordinates = targetFeature.values_.geometry.flatCoordinates;

          // eslint-disable-next-line no-underscore-dangle
          overlay.setPosition(coordinates);

          if (useLocations) {
            const name = targetFeature.values_.features[0].values_.name ?? "";
            const children = targetFeature.values_.features[0].values_.children ?? "";
            const locationId = targetFeature.values_.features[0].values_.locationId ?? "";

            // eslint-disable-next-line no-underscore-dangle
            tooltip.innerHTML = `<div class='tooltip-closer'>✖️</div>
              <div class='tooltip-text'>
                  <span><b>${name}</b></span><span>${children} test</span>
                  <a data-location="${locationId}"
                    data-location-name="${name}"
                    class='tooltip-btn'">
                    Vis i kalender
                  </a>
              </div>`;
          } else {
            const name = targetFeature.values_.features[0].values_.name ?? "";
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
          setResourceFilter([
            {
              value: event.target.getAttribute("data-resource-mail"),
              label: event.target.getAttribute("data-resource-name"),
            },
          ]);
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
