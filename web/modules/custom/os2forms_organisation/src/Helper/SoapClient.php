<?php

namespace Drupal\os2forms_organisation\Helper;

/**
 * Soap client.
 */
class SoapClient {

  /**
   * Executes Soap request.
   */
  public static function doSoap($url, $request, $action = NULL) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSLVERSION, 6);

    if ($action != NULL) {
      $headers = [
        'Content-Type: application/soap+xml; charset=utf-8; action="' . $action . '"',
        "Content-Length: " . strlen($request),
      ];
    }
    else {
      $headers = [
        'Content-Type: application/soap+xml; charset=utf-8',
        "Content-Length: " . strlen($request),
      ];
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($request != NULL) {
<<<<<<< HEAD
<<<<<<< HEAD
=======
      // Workaround curl version peculiarity.
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    }

    $result = curl_exec($ch);

    curl_close($ch);

    return $result;
  }

}
