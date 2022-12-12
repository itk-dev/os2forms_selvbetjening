<?php

namespace Drupal\os2forms_organisation\Helper;

// phpcs:ignore
use DOMXPath;

/**
 * Helper class for SF1514 Security Token Service.
 */
class SecurityTokenService {
  const TOKENTYPE_SAML20 = 'http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.1#SAMLV2.0';
  const TOKENTYPE_STATUS = 'http://docs.oasis-open.org/ws-sx/ws-trust/200512/RSTR/Status';

  const KEYTYPE_BEARER = 'http://docs.oasis-open.org/ws-sx/ws-trust/200512/Bearer';
  const KEYTYPE_PUBLIC = 'http://docs.oasis-open.org/ws-sx/ws-trust/200512/PublicKey';

  /**
<<<<<<< HEAD
<<<<<<< HEAD
   * Builds SAML token request XML.
   */
  // phpcs:ignore
  public function buildSAMLTokenRequestXML($cert, $privKey, $cvr, $appliesTo) {
    $dom = new \DOMDocument();
    $dom->load(__DIR__ . '/SAMLTokenSoapTemplate.xml');
    $xpath = new DOMXPath($dom);

    $xpath->registerNamespace('wsu', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd');
    $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    $xpath->registerNamespace('wst', 'http://docs.oasis-open.org/ws-sx/ws-trust/200512');
    $xpath->registerNamespace('wsp', 'http://schemas.xmlsoap.org/ws/2004/09/policy');
    $xpath->registerNamespace('wsa', 'http://www.w3.org/2005/08/addressing');
    $xpath->registerNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
    $xpath->registerNamespace('wsauth', 'http://docs.oasis-open.org/wsfed/authorization/200706');
    $xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');

    // Signature.
    $signatureId = 'SIG-' . $this->generateUuid();
    $signature = $this->getElement($xpath, '//ds:Signature');
    $signature->setAttribute('Id', $signatureId);

    // Action.
    $actionId = '_' . $this->generateUuid();
    $actionElement = $this->getElement($xpath, '//wsa:Action');
    $actionElement->setAttribute('wsu:Id', $actionId);

    $this->handleReference($xpath, $actionElement, $actionId, 'action_id');

    // MessageID.
    $messageId = '_' . $this->generateUuid();
    $messageIdElement = $this->getElement($xpath, '//wsa:MessageID');
    $messageIdElement->setAttribute('wsu:Id', $messageId);
    $messageIdElement->nodeValue = 'urn:uuid:' . $this->generateUuid();

    $this->handleReference($xpath, $messageIdElement, $messageId, 'message_id_id');

    // To.
    $toId = '_' . $this->generateUuid();
    $toElement = $this->getElement($xpath, '//wsa:To');
    $toElement->nodeValue = $appliesTo;
    $toElement->setAttribute('wsu:Id', $toId);

    $this->handleReference($xpath, $toElement, $toId, 'to_id');

    // ReplyTo.
    $replyToId = '_' . $this->generateUuid();
    $replyToElement = $this->getElement($xpath, '//wsa:ReplyTo');
    $replyToElement->setAttribute('wsu:Id', $replyToId);

    $this->handleReference($xpath, $replyToElement, $replyToId, 'reply_id');

    // Timestamp.
    $timestampId = 'TS-' . $this->generateUuid();
    $timestampElement = $this->getElement($xpath, '//wsu:Timestamp');
    $timestampElement->setAttribute('wsu:Id', $timestampId);

    $this->getElement($xpath, 'wsu:Created', $timestampElement)->nodeValue = $this->getTimestamp();
    $this->getElement($xpath, 'wsu:Expires', $timestampElement)->nodeValue = $this->getTimestamp(300);

    $this->handleReference($xpath, $timestampElement, $timestampId, 'timestamp_id');

    // BinarySecurityToken.
    $certificateKeyContent = str_replace(["\r", "\n"], '', $cert);

    $binarySecurityTokenId = 'X509-' . $this->generateUuid();
    $binarySecurityTokenElement = $this->getElement($xpath, '//wsse:BinarySecurityToken');
    $binarySecurityTokenElement->setAttribute('wsu:Id', $binarySecurityTokenId);
    $binarySecurityTokenElement->nodeValue = $certificateKeyContent;

    $this->handleReference($xpath, $binarySecurityTokenElement, $binarySecurityTokenId, 'security_token_id');

    // Body.
    $bodyId = '_' . $this->generateUuid();
    $bodyElement = $this->getElement($xpath, '//soap:Body');
    $bodyElement->setAttribute('wsu:Id', $bodyId);

    $this->getElement($xpath, '//wsauth:Value')->nodeValue = $cvr;
    $this->getElement($xpath, 'wsse:BinarySecurityToken', $this->getElement($xpath, '//wst:UseKey'))->nodeValue = $certificateKeyContent;

    $this->handleReference($xpath, $bodyElement, $bodyId, 'body_id');

    // KeyInfo.
    $keyInfoId = 'KI-' . $this->generateUuid();
    $keyInfoElement = $this->getElement($xpath, '//ds:KeyInfo');
    $keyInfoElement->setAttribute('Id', $keyInfoId);

    // Set final ids.
    $this->getElement($xpath, '//wsse:Reference')->setAttribute('URI', '#' . $binarySecurityTokenId);
    $this->getElement($xpath, '//wsse:SecurityTokenReference')->setAttribute('wsu:Id', 'STR-' . $this->generateUuid());

    // Sign the request.
    $signedInfoElement = $this->getElement($xpath, '//ds:SignedInfo');

    $signedIntoElementCanonical = $signedInfoElement->C14N(TRUE, FALSE);
    openssl_sign($signedIntoElementCanonical, $signatureValue, $privKey, 'sha256WithRSAEncryption');

    $signatureValue = base64_encode($signatureValue);
    $this->getElement($xpath, '//ds:SignatureValue')->nodeValue = $signatureValue;

    return $dom->saveXML();
  }

  /**
   * Handles reference by signing element.
   */
  private function handleReference(DOMXPath $xpath, \DOMElement $element, string $elementId, $baseId) {
    $referenceElement = $this->getElement($xpath, "//ds:Reference[contains(@URI, '$baseId')]");
    $referenceElement->setAttribute('URI', '#' . $elementId);

    $digestValue = base64_encode(openssl_digest($element->C14N(TRUE, FALSE), 'SHA256', TRUE));
    $this->getElement($xpath, 'ds:DigestValue', $referenceElement)->nodeValue = $digestValue;
  }

  /**
   * Queries for element.
   */
  private function getElement(DOMXPath $xpath, string $expression, \DOMElement $context = NULL): \DOMElement {
    return $xpath->query($expression, $context)[0];
=======
   * Computes Request Security Token (RST) XML.
=======
   * Builds SAML token request XML.
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
   */
  public function buildSAMLTokenRequestXML($cert, $privKey, $cvr, $appliesTo) {
    $dom = new \DOMDocument();
    $dom->load(__DIR__.'/SAMLTokenSoapTemplate.xml');
    $xpath = new DOMXPath($dom);

<<<<<<< HEAD
    $certKey = str_replace(["\r", "\n"], '', $certKey);
    $bodyId = self::generateUuid();
=======
    $xpath->registerNamespace('wsu', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd');
    $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    $xpath->registerNamespace('wst', 'http://docs.oasis-open.org/ws-sx/ws-trust/200512');
    $xpath->registerNamespace('wsp', 'http://schemas.xmlsoap.org/ws/2004/09/policy');
    $xpath->registerNamespace('wsa', 'http://www.w3.org/2005/08/addressing');
    $xpath->registerNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
    $xpath->registerNamespace('wsauth', 'http://docs.oasis-open.org/wsfed/authorization/200706');
    $xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

    // Signature
    $signatureId = 'SIG-'.$this->generateUuid();
    $signature = $this->getElement($xpath, '//ds:Signature');
    $signature->setAttribute('Id', $signatureId);

<<<<<<< HEAD
    $header = self::getRequestSecurityTokenHeader($endpointSts, $certKey);
=======
    //Action
    $actionId = '_'.$this->generateUuid();
    $actionElement = $this->getElement($xpath, '//wsa:Action');
    $actionElement->setAttribute('wsu:Id', $actionId);
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

    $this->handleReference($xpath, $actionElement, $actionId,'action_id');

    // MessageID
    $messageId = '_'.$this->generateUuid();
    $messageIdElement = $this->getElement($xpath, '//wsa:MessageID');
    $messageIdElement->setAttribute('wsu:Id', $messageId);
    $messageIdElement->nodeValue = 'urn:uuid:' . $this->generateUuid();

<<<<<<< HEAD
    $token = self::getCertificateToken($certKey, self::generateUuid());
    $timestamp = self::getTimestampHeader(self::generateUuid());
    $action = '<Action xmlns="http://www.w3.org/2005/08/addressing" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="_' . self::generateUuid() . '">' . $action . '</Action>';
    $message = '<MessageID xmlns="http://www.w3.org/2005/08/addressing" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="_' . self::generateUuid() . '">urn:uuid:' . self::generateUuid() . '</MessageID>';
    $reply = '<ReplyTo xmlns="http://www.w3.org/2005/08/addressing" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="_' . self::generateUuid() . '"><Address>http://www.w3.org/2005/08/addressing/anonymous</Address></ReplyTo>';
    $to = '<To xmlns="http://www.w3.org/2005/08/addressing" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="_' . self::generateUuid() . '">' . $to . '</To>';
=======
    $this->handleReference($xpath, $messageIdElement, $messageId,'message_id_id');
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

    // To
    $toId = '_'.$this->generateUuid();
    $toElement = $this->getElement($xpath, '//wsa:To');
    $toElement->nodeValue = $appliesTo;
    $toElement->setAttribute('wsu:Id', $toId);

    $this->handleReference($xpath, $toElement, $toId,'to_id');

<<<<<<< HEAD
    $tokenUuid = self::getDocEleId($documentRequest->getElementsByTagName('BinarySecurityToken')[0]);
    $signatureUuid = self::generateUuid();
    $keyInfoUuid = self::generateUuid();
    $tokRefUuid = self::generateUuid();

    $references = self::getReferenceByTag('Timestamp', $request);
    $references .= self::getReferenceByTag('Body', $request);
    $references .= self::getReferenceByTag('To', $request);
    $references .= self::getReferenceByTag('ReplyTo', $request);
    $references .= self::getReferenceByTag('MessageID', $request);
    $references .= self::getReferenceByTag('Action', $request);
    $references .= self::getReferenceByTag('BinarySecurityToken', $request);
=======
    // ReplyTo
    $replyToId = '_'.$this->generateUuid();
    $replyToElement = $this->getElement($xpath, '//wsa:ReplyTo');
    $replyToElement->setAttribute('wsu:Id', $replyToId);

    $this->handleReference($xpath, $replyToElement, $replyToId,'reply_id');
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

    // Timestamp
    $timestampId = 'TS-'.$this->generateUuid();
    $timestampElement = $this->getElement($xpath, '//wsu:Timestamp');
    $timestampElement->setAttribute('wsu:Id', $timestampId);

    $this->getElement($xpath, 'wsu:Created', $timestampElement)->nodeValue = $this->getTimestamp();
    $this->getElement($xpath, 'wsu:Expires', $timestampElement)->nodeValue = $this->getTimestamp(300);

    $this->handleReference($xpath, $timestampElement, $timestampId, 'timestamp_id');

    // BinarySecurityToken
    $certificateKeyContent = str_replace(["\r", "\n"], '', $cert);

    $binarySecurityTokenId = 'X509-'.$this->generateUuid();
    $binarySecurityTokenElement = $this->getElement($xpath, '//wsse:BinarySecurityToken');
    $binarySecurityTokenElement->setAttribute('wsu:Id', $binarySecurityTokenId);
    $binarySecurityTokenElement->nodeValue = $certificateKeyContent;

    $this->handleReference($xpath, $binarySecurityTokenElement, $binarySecurityTokenId, 'security_token_id');

    // Body
    $bodyId = '_'.$this->generateUuid();
    $bodyElement = $this->getElement($xpath, '//soap:Body');
    $bodyElement->setAttribute('wsu:Id', $bodyId);

    $this->getElement($xpath, '//wsauth:Value')->nodeValue = $cvr;
    $this->getElement($xpath, 'wsse:BinarySecurityToken', $this->getElement($xpath, '//wst:UseKey'))->nodeValue = $certificateKeyContent;

    $this->handleReference($xpath, $bodyElement, $bodyId, 'body_id');

    // KeyInfo
    $keyInfoId = 'KI-'.$this->generateUuid();
    $keyInfoElement = $this->getElement($xpath, '//ds:KeyInfo');
    $keyInfoElement->setAttribute('Id', $keyInfoId);

    // Set final ids
    $this->getElement($xpath, '//wsse:Reference')->setAttribute('URI', '#'.$binarySecurityTokenId);
    $this->getElement($xpath, '//wsse:SecurityTokenReference')->setAttribute('wsu:Id', 'STR-'.$this->generateUuid());

    // Sign the request
    $signedInfoElement = $this->getElement($xpath, '//ds:SignedInfo');

    $signedIntoElementCanonical = $signedInfoElement->C14N(TRUE, FALSE);
    openssl_sign($signedIntoElementCanonical, $signatureValue, $privKey, 'sha256WithRSAEncryption');

    $signatureValue = base64_encode($signatureValue);
    $this->getElement($xpath, '//ds:SignatureValue')->nodeValue = $signatureValue;

    return $dom->saveXML();
  }

  private function handleReference(DOMXPath $xpath, \DOMElement $element, string $elementId, $baseId) {
    $referenceElement = $this->getElement($xpath, "//ds:Reference[contains(@URI, '$baseId')]");
    $referenceElement->setAttribute('URI', '#'.$elementId);

    $digestValue = base64_encode(openssl_digest($element->C14N(TRUE, FALSE), 'SHA256', TRUE));
    $this->getElement($xpath, 'ds:DigestValue', $referenceElement)->nodeValue = $digestValue;
  }

<<<<<<< HEAD
  /**
   * Computes reference XML by tag.
   */
  public function getReferenceByTag($tagName, $request) {

    $dom = new \DOMDocument();
    $dom->loadXML($request);
    $tag = $dom->getElementsByTagName($tagName)[0];
    $canonicalXml = $tag->C14N(TRUE, FALSE);

    $digestValue = base64_encode(openssl_digest($canonicalXml, 'SHA256', TRUE));

    // Extract "Id" attribute from xml data.
    $refURI = self::getDocEleId($tag);

    return <<<XML
<ds:Reference URI="#$refURI">
    <ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></ds:Transform></ds:Transforms>
    <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
    <ds:DigestValue>$digestValue</ds:DigestValue>
</ds:Reference>
XML;
  }

  /**
   * Computes RST security token XML.
   */
  public function getCertificateToken($certificateKey, $tokenTagUuid) {
    $certificateKeyContent = str_replace(["\r", "\n"], '', $certificateKey);
    return <<<XML
<wsse:BinarySecurityToken xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3" wsu:Id="X509-$tokenTagUuid">$certificateKeyContent</wsse:BinarySecurityToken>
XML;
>>>>>>> 2552f8f (DW-454: Organisationsdata)
  }
=======
  private function getElement(DOMXPath $xpath, string $expression, \DOMElement $context = null): \DOMElement {
    return $xpath->query($expression, $context)[0];
  }

>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

  /**
   * Computes timestamp.
   */
  public function getTimestamp($offset = 0) {
    return gmdate("Y-m-d\TH:i:s\Z", time() + $offset);
  }

  /**
<<<<<<< HEAD
<<<<<<< HEAD
=======
   * Computes XML timestamp header.
   */
  public function getTimestampHeader($timestampID = "_0") {
    $created = self::getTimestamp();
    $expires = self::getTimestamp(300);
    return <<<XML
<wsu:Timestamp wsu:Id="TS-$timestampID">
    <wsu:Created>$created</wsu:Created>
    <wsu:Expires>$expires</wsu:Expires>
</wsu:Timestamp>
XML;
  }

  /**
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
   * Generates uuid.
   */
  public function generateUuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      // 32 bits for "time_low"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),

      // 16 bits for "time_mid"
      mt_rand(0, 0xffff),

      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand(0, 0x0fff) | 0x4000,

      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand(0, 0x3fff) | 0x8000,

      // 48 bits for "node"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
  }

  /**
   * Parses Request Security Token Response (RSTR).
   */
  public function parseRequestSecurityTokenResponse($result) {
    $dom = new \DOMDocument();
    $dom->loadXML($result);
    $doc = $dom->documentElement;
    $xpath = new DOMXpath($dom);
    $xpath->registerNamespace('s', 'http://www.w3.org/2003/05/soap-envelope');
    $xpath->registerNamespace('wst', 'http://docs.oasis-open.org/ws-sx/ws-trust/200512');
    $xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
    $token = $xpath->query('/s:Envelope/s:Body/wst:RequestSecurityTokenResponseCollection/wst:RequestSecurityTokenResponse/wst:RequestedSecurityToken', $doc);
    $proofKey = $xpath->query('/s:Envelope/s:Body/wst:RequestSecurityTokenResponseCollection/wst:RequestSecurityTokenResponse/wst:RequestedProofToken/wst:BinarySecret', $doc);
    if ($proofKey->length > 0) {
      $proofKey = base64_decode($proofKey->item(0)->textContent);
    }
    else {
      $proofKey = NULL;
    }
    return [$dom, $xpath, $token->item(0), $proofKey];
  }

  /**
   * Decrypts Request Security Token Response (RSTR).
   */
<<<<<<< HEAD
<<<<<<< HEAD
  public function getDecrypted(\DOMDocument $dom, $xpath, $token, $pkey, $type = self::TOKENTYPE_SAML20) {

=======
  public function getDecrypted($dom, $xpath, $token, $pkey, $type = self::TOKENTYPE_SAML20) {
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
  public function getDecrypted(\DOMDocument $dom, $xpath, $token, $pkey, $type = self::TOKENTYPE_SAML20) {

>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    $doc = $dom->documentElement;
    $xpath->registerNamespace('xenc', 'http://www.w3.org/2001/04/xmlenc#');
    $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
    $xpathPrefix = '/s:Envelope/s:Body/wst:RequestSecurityTokenResponseCollection/wst:RequestSecurityTokenResponse/wst:RequestedSecurityToken';

    $xpathSuffix = '/saml:Assertion';
    $data = $xpath->query($xpathPrefix . $xpathSuffix, $doc);

    if ($data->length > 0) {
      $token = $data->item(0);
    }

    return [$dom, $token];
  }

}
