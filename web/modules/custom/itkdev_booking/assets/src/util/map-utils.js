import Proj4 from "proj4";

/**
 * @param {number} lat Latitude coordinate
 * @param {number} long Longitude coordinate
 * @returns {object} Proj object containing converted coordinates
 */
export function latlngToUTM(lat, long) {
  const parsedLat = parseFloat(lat);
  const parsedLong = parseFloat(long);
  const wgs84 = "+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs";
  const utm = "+proj=utm +zone=32";

  return Proj4(wgs84, utm, [parsedLong, parsedLat]);
}

/**
 * @param {object} resources Resources array
 * @returns {Array} Containing openLayer features and tooltip content
 */
export function getFeatures(resources) {
  // Loop resources and build coordinates and tooltip content
  const locations = [];

  Object.values(resources).forEach((value) => {
    if (value.location in locations) {
      locations[value.location].resource_count += 1;
    } else {
      if (value.location === "" || value.geoCoordinates === "" || value.geoCoordinates === null) {
        return;
      }
      const geoCoordinates = value.geoCoordinates.split(",");
      const utmCoordinates = latlngToUTM(geoCoordinates[0], geoCoordinates[1]);

      locations[value.location] = {
        location: value.locationDisplayName ?? value.location,
        locationId: value.location,
        northing: utmCoordinates[0],
        easting: utmCoordinates[1],
        resource_count: 1,
      };
    }
  });

  return locations;
}
