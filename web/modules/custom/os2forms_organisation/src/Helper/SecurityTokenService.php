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
   * Computes Request Security Token (RST) XML.
   */
  // phpcs:ignore
  public function getRequestSecurityTokenXML($endpointSts, $appliesTo, $cvr, $issuer, $certKey, $action = 'Issue', $keyType = self::KEYTYPE_PUBLIC, $tokenType = self::TOKENTYPE_SAML20) {

    $certKey = str_replace(["\r", "\n"], '', $certKey);
    $bodyId = $this->generateUuid();

    $body = <<<XML
<soap:Body xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="_$bodyId">
    <wst:RequestSecurityToken xmlns:wst="http://docs.oasis-open.org/ws-sx/ws-trust/200512">
        <wst:RequestType>http://docs.oasis-open.org/ws-sx/ws-trust/200512/$action</wst:RequestType>
        <wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">
            <wsa:EndpointReference xmlns:wsa="http://www.w3.org/2005/08/addressing">
              <wsa:Address>$appliesTo</wsa:Address>
            </wsa:EndpointReference>
        </wsp:AppliesTo>
        <Claims xmlns="http://docs.oasis-open.org/ws-sx/ws-trust/200512" Dialect="http://docs.oasis-open.org/wsfed/authorization/200706/authclaims">
            <ClaimType xmlns="http://docs.oasis-open.org/wsfed/authorization/200706" Uri="dk:gov:saml:attribute:CvrNumberIdentifier">
                <Value xmlns="http://docs.oasis-open.org/wsfed/authorization/200706">$cvr</Value>
            </ClaimType>
        </Claims>
        <wst:TokenType>$tokenType</wst:TokenType>
        <wst:KeyType>$keyType</wst:KeyType>
        <wst:UseKey>
            <wsse:BinarySecurityToken xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3">$certKey</wsse:BinarySecurityToken>
        </wst:UseKey>
    </wst:RequestSecurityToken>
</soap:Body>
XML;

    $header = $this->getRequestSecurityTokenHeader($endpointSts, $certKey);

    return <<<XML
        <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
            $header
            $body
        </soap:Envelope>
        XML;
  }

  /**
   * Computes Request Security Token (RST) XML header.
   */
  public function getRequestSecurityTokenHeader($to, $certKey, $action = 'http://docs.oasis-open.org/ws-sx/ws-trust/200512/RST/Issue') {

    $token = $this->getCertificateToken($certKey, $this->generateUuid());
    $timestamp = $this->getTimestampHeader($this->generateUuid());
    $action = '<Action xmlns="http://www.w3.org/2005/08/addressing" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="_' . $this->generateUuid() . '">' . $action . '</Action>';
    $message = '<MessageID xmlns="http://www.w3.org/2005/08/addressing" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="_' . $this->generateUuid() . '">urn:uuid:' . $this->generateUuid() . '</MessageID>';
    $reply = '<ReplyTo xmlns="http://www.w3.org/2005/08/addressing" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="_' . $this->generateUuid() . '"><Address>http://www.w3.org/2005/08/addressing/anonymous</Address></ReplyTo>';
    $to = '<To xmlns="http://www.w3.org/2005/08/addressing" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="_' . $this->generateUuid() . '">' . $to . '</To>';

    return <<<XML
<soap:Header>
    $action
    $message
    $to
    $reply
    <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" soap:mustUnderstand="true">
        $timestamp
        $token
    </wsse:Security>
</soap:Header>
XML;
  }

  /**
   * Signs Request Security Token (RST).
   */
  public function signRequestSecurityToken($request, $priv_key) {
    $documentRequest = new \DOMDocument();
    $documentRequest->preserveWhiteSpace = TRUE;
    $documentRequest->formatOutput = TRUE;
    $documentRequest->loadXML($request);

    $tokenUuid = $this->getDocEleId($documentRequest->getElementsByTagName('BinarySecurityToken')[0]);
    $signatureUuid = $this->generateUuid();
    $keyInfoUuid = $this->generateUuid();
    $tokRefUuid = $this->generateUuid();

    $references = $this->getReferenceByTag('Timestamp', $request);
    $references .= $this->getReferenceByTag('Body', $request);
    $references .= $this->getReferenceByTag('To', $request);
    $references .= $this->getReferenceByTag('ReplyTo', $request);
    $references .= $this->getReferenceByTag('MessageID', $request);
    $references .= $this->getReferenceByTag('Action', $request);
    $references .= $this->getReferenceByTag('BinarySecurityToken', $request);

    $signedInfo = <<<XML
<ds:SignedInfo>
    <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
    <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
    $references
</ds:SignedInfo>
XML;

    $signature = <<<XML
<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="SIG-$signatureUuid">
    $signedInfo
    <ds:SignatureValue></ds:SignatureValue>
    <ds:KeyInfo Id="KI-$keyInfoUuid">
        <wsse:SecurityTokenReference xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="STR-$tokRefUuid">
          <wsse:Reference URI="#$tokenUuid" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3"/>
        </wsse:SecurityTokenReference>
    </ds:KeyInfo>
</ds:Signature>
XML;
    $documentSignature = new \DOMDocument();
    $documentSignature->loadXML($signature);
    $signatureElement = $documentSignature->getElementsByTagName('SignedInfo')[0];
    $signatureElementCanonical = $signatureElement->C14N(TRUE, FALSE);

    // OPENSSL_ALGO_SHA256 OR 'RSA-SHA256'.
    openssl_sign($signatureElementCanonical, $signatureValue, $priv_key, 'sha256WithRSAEncryption');

    $signatureValue = base64_encode($signatureValue);

    // Insert signaturevalue.
    $documentSignature->getElementsByTagName('SignatureValue')[0]->nodeValue = $signatureValue;

    // Insert signature in header....
    $node = $documentRequest->importNode($documentSignature->documentElement, TRUE);

    $documentRequest->getElementsByTagName('Security')[0]->appendChild($node);

    return $documentRequest->saveXML($documentRequest->documentElement);
  }

  /**
   * Extract "Id" attribute from xml data.
   */
  public function getDocEleId($docEle) {
    for ($i = 0; $i < $docEle->attributes->length; ++$i) {
      if (strpos($docEle->attributes->item($i)->name, 'Id') !== FALSE) {
        return $docEle->attributes->item($i)->value;
      }
    }
    return NULL;
  }

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
    $refURI = $this->getDocEleId($tag);

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
  }

  /**
   * Computes timestamp.
   */
  public function getTimestamp($offset = 0) {
    return gmdate("Y-m-d\TH:i:s\Z", time() + $offset);
  }

  /**
   * Computes XML timestamp header.
   */
  public function getTimestampHeader($timestampID = "_0") {
    $created = $this->getTimestamp();
    $expires = $this->getTimestamp(300);
    return <<<XML
<wsu:Timestamp wsu:Id="TS-$timestampID">
    <wsu:Created>$created</wsu:Created>
    <wsu:Expires>$expires</wsu:Expires>
</wsu:Timestamp>
XML;
  }

  /**
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
  public function getDecrypted($dom, $xpath, $token, $pkey, $type = self::TOKENTYPE_SAML20) {
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
