<?php

namespace Drupal\os2forms_organisation\Helper;

/**
 * Helper class for SF1500 Organisation.
 */
class OrganisationService {

  /**
   * Computes XML body for bruger laes.
   */
  public function getBodyBrugerLaes($uuid) {
    return <<<XML
<s:Body u:Id="_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <LaesInput xmlns="http://stoettesystemerne.dk/organisation/bruger/6/">
        <UUIDIdentifikator xmlns="urn:oio:sagdok:3.0.0">$uuid</UUIDIdentifikator>
    </LaesInput>
</s:Body>
XML;
  }

  /**
   * Computes XML body for person laes.
   */
  public function getBodyPersonLaes($uuid) {
    return <<<XML
<s:Body u:Id="_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <LaesInput xmlns="http://stoettesystemerne.dk/organisation/person/6/">
        <UUIDIdentifikator xmlns="urn:oio:sagdok:3.0.0">$uuid</UUIDIdentifikator>
    </LaesInput>
</s:Body>
XML;
  }

  /**
   * Computes XML body for person list.
   */
  public function getBodyPersonList($uuid) {
    return <<<XML
<s:Body u:Id="_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <ListInput xmlns="http://stoettesystemerne.dk/organisation/person/6/">
        <UUIDIdentifikator xmlns="urn:oio:sagdok:3.0.0">$uuid</UUIDIdentifikator>
    </ListInput>
</s:Body>
XML;
  }

  /**
   * Computes XML body for adresse laes.
   */
  public function getBodyAdresseLaes($uuid) {
    return <<<XML
<s:Body u:Id="_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <LaesInput xmlns="http://stoettesystemerne.dk/organisation/adresse/6/">
        <UUIDIdentifikator xmlns="urn:oio:sagdok:3.0.0">$uuid</UUIDIdentifikator>
    </LaesInput>
</s:Body>
XML;
  }

  /**
   * Computes XML body for organisation funktion laes.
   */
  public function getBodyOrganisationFunktionLaes(string $uuid) {
    return <<<XML
<s:Body u:Id="_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <LaesInput xmlns="http://stoettesystemerne.dk/organisation/organisationfunktion/6/">
        <UUIDIdentifikator xmlns="urn:oio:sagdok:3.0.0">$uuid</UUIDIdentifikator>
    </LaesInput>
</s:Body>
XML;
  }

  /**
   * Computes XML body for organisation funktion soeg.
   */
  public function getBodyOrganisationFunktionSoeg(?string $uuid, ?string $funktionNavn) {
    $funktionNavnXML = '';
    $uuidXML = '';

    if ($funktionNavn !== NULL) {
      $funktionNavnXML = <<<XML
        <FunktionNavn xmlns="urn:oio:sagdok:3.0.0">$funktionNavn</FunktionNavn>
XML;
    }

    if ($uuid !== NULL) {
      $uuidXML = <<<XML
      <TilknyttedeBrugere xmlns="urn:oio:sagdok:3.0.0">
        <ReferenceID>
          <UUIDIdentifikator xmlns="urn:oio:sagdok:3.0.0">$uuid</UUIDIdentifikator>
        </ReferenceID>
      </TilknyttedeBrugere>
XML;
    }

    return <<<XML
<s:Body u:Id="_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <SoegInput xmlns="http://stoettesystemerne.dk/organisation/organisationfunktion/6/">
      <AttributListe>
        <Egenskab>
          $funktionNavnXML
        </Egenskab>
      </AttributListe>
      <TilstandListe/>
      <RelationListe>
        $uuidXML
      </RelationListe>
    </SoegInput>
</s:Body>
XML;
  }

  /**
   * Computes XML body for fremsoeg hieraki.
   */
  public function getBodyFremsoegHieraki(string $name) {
    return <<<XML
<s:Body u:Id="_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <FremsoegObjekthierarkiInput xmlns="http://stoettesystemerne.dk/organisation/organisationsystem/6/">
        <OrganisationEnhedSoegEgenskab>
            <EnhedNavn xmlns="urn:oio:sagdok:3.0.0">$name</EnhedNavn>
        </OrganisationEnhedSoegEgenskab>
    </FremsoegObjekthierarkiInput>
</s:Body>
XML;
  }

  /**
   * Computes XML body for person soeg.
   */
  public function getBodyPersonSoeg(string $name) {
    return <<<XML
<s:Body u:Id="_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <SoegInput xmlns="http://stoettesystemerne.dk/organisation/person/6/">
      <AttributListe>
        <Egenskab>
          <NavnTekst xmlns="http://stoettesystemerne.dk/organisation/person/6/">$name</NavnTekst>
        </Egenskab>
      </AttributListe>
      <TilstandListe/>
      <RelationListe/>
    </SoegInput>
</s:Body>
XML;
  }

  /**
   * Computes XML body for bruger soeg.
   */
  public function getBodyBrugerSoeg(string $name) {
    return <<<XML
<s:Body u:Id="_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <SoegInput xmlns="http://stoettesystemerne.dk/organisation/bruger/6/">
      <AttributListe>
        <Egenskab>
          <BrugerNavn xmlns="urn:oio:sagdok:3.0.0">$name</BrugerNavn>
        </Egenskab>
      </AttributListe>
      <TilstandListe/>
      <RelationListe/>
    </SoegInput>
</s:Body>
XML;
  }

  /**
   * Computes XML body for organisation enhed laes.
   */
  public function getBodyOrganisationEnhedLaes(string $string) {
    return <<<XML
<s:Body u:Id="_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <LaesInput xmlns="http://stoettesystemerne.dk/organisation/organisationenhed/6/">
        <UUIDIdentifikator xmlns="urn:oio:sagdok:3.0.0">$string</UUIDIdentifikator>
    </LaesInput>
</s:Body>
XML;
  }

  /**
   * Computes XML header.
   */
  public function getHeader($to, $action, $token_raw) {

    $_timestamp = self::getTimestampHeader(self::generateUuid());
    $_action = '<a:Action s:mustUnderstand="1" u:Id="_2">' . $action . '</a:Action>';
    $_message = '<a:MessageID u:Id="_3">urn:uuid:' . self::generateUuid() . '</a:MessageID>';
    $_reply = '<a:ReplyTo u:Id="_4"><a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address></a:ReplyTo>';
    $_to = '<a:To s:mustUnderstand="1" u:Id="_5">' . $to . '</a:To>';

    $trans_uuid = self::generateUuid();

    // @todo Make both below dynamic with parameters.... maybe ns (namespaces) vary from service to service... must generate request from WSDL?
    $_request_header = <<<XML
<h:RequestHeader xmlns:h="http://kombit.dk/xml/schemas/RequestHeader/1/" xmlns="http://kombit.dk/xml/schemas/RequestHeader/1/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <TransactionUUID>$trans_uuid</TransactionUUID>
</h:RequestHeader>
XML;

    $d_t = new \DOMDocument();
    $d_t->loadXML($token_raw);
    $token_uuid = self::getDocEleId($d_t->documentElement);
    return <<<XML
<s:Header>
    <sbf:Framework xmlns:ns1="urn:liberty:sb:profile" xmlns:sbf="urn:liberty:sb:2006-08" ns1:profile="urn:liberty:sb:profile:basic" version="2.0"/>
    $_action
    $_request_header
    $_message
    $_reply
    $_to
    <o:Security s:mustUnderstand="1" xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
        $_timestamp
        $token_raw
        <o:SecurityTokenReference b:TokenType="http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.1#SAMLV2.0" u:Id="_str$token_uuid" xmlns:b="http://docs.oasis-open.org/wss/oasis-wss-wssecurity-secext-1.1.xsd">
            <o:KeyIdentifier ValueType="http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.1#SAMLID">$token_uuid</o:KeyIdentifier>
        </o:SecurityTokenReference>
        <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
            <SignedInfo>
                <CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></CanonicalizationMethod>
                <SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"></SignatureMethod>
            </SignedInfo>
            <SignatureValue></SignatureValue>
            <KeyInfo>
                <o:SecurityTokenReference b:TokenType="http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.1#SAMLV2.0" xmlns:b="http://docs.oasis-open.org/wss/oasis-wss-wssecurity-secext-1.1.xsd">
                    <o:KeyIdentifier ValueType="http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.1#SAMLID">$token_uuid</o:KeyIdentifier>
                </o:SecurityTokenReference>
            </KeyInfo>
        </Signature>
    </o:Security>
</s:Header>
XML;
  }

  /**
   * Signs request.
   */
  public function getRequestSigned($request_simple, $priv_key) {

    $d_r = new \DOMDocument('1.0', 'utf-8');
    $d_r->preserveWhiteSpace = FALSE;
    $d_r->formatOutput = FALSE;
    $d_r->loadXML($request_simple);

    $sig_ele = $d_r->getElementsByTagName('Signature')[1];
    $si_ele = $sig_ele->getElementsByTagName('SignedInfo')[0];

    $referenceIds = [
      'Body',
      'Action',
      'MessageID',
      'ReplyTo',
      'To',
      'Timestamp',
      'SecurityTokenReference',
    ];

    foreach ($referenceIds as &$value) {
      $isSTR = ($value == 'SecurityTokenReference');

      $tags = $d_r->getElementsByTagName($value);

      $tag = $tags[0];
      $tag_id = self::getDocEleId($tag);

      if ($isSTR) {
        $tag = $d_r->getElementsByTagName('Assertion')[0];
      }

      $canonicalXml = utf8_encode($tag->C14N(TRUE, FALSE));

      $digestValue = base64_encode(openssl_digest($canonicalXml, 'sha256', TRUE));

      $reference = $si_ele->appendChild($d_r->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Reference'));
      $reference->setAttribute('URI', "#{$tag_id}");
      $transforms = $reference->appendChild($d_r->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Transforms'));
      $transform = $transforms->appendChild($d_r->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Transform'));

      if ($isSTR) {
        $transform->setAttribute('Algorithm', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#STR-Transform');
        $transformationParameter = $transform->appendChild($d_r->createElementNS('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'TransformationParameters'));
        $canonicalizationMethod = $transformationParameter->appendChild($d_r->createELementNS('http://www.w3.org/2000/09/xmldsig#', 'CanonicalizationMethod'));
        $canonicalizationMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
      }
      else {
        $transform->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
      }

      $method = $reference->appendChild($d_r->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'DigestMethod'));
      $method->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
      $reference->appendChild($d_r->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'DigestValue', $digestValue));
    }

    $si_ele_can = $si_ele->C14N(TRUE, FALSE);

    // OPENSSL_ALGO_SHA256 OR 'RSA-SHA256' OR 'sha256WithRSAEncryption'.
    openssl_sign($si_ele_can, $signatureValue, $priv_key, 'sha256WithRSAEncryption');
    $signatureValue = base64_encode($signatureValue);

    // Insert signaturevalue.
    $sig_ele->getElementsByTagName('SignatureValue')[0]->nodeValue = $signatureValue;

    return $d_r->saveXML($d_r->documentElement);

  }

  /**
   * Extract "Id" attribute from xml data.
   */
  public function getDocEleId($docEle) {
    for ($i = 0; $i < $docEle->attributes->length; ++$i) {
      if (strpos($docEle->attributes->item($i)->name, 'Id') !== FALSE || strpos($docEle->attributes->item($i)->name, 'ID') !== FALSE) {
        return $docEle->attributes->item($i)->value;
      }
    }
    return NULL;
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
    $c = self::getTimestamp();
    $e = self::getTimestamp(300);
    return <<<XML
<u:Timestamp u:Id="uuid-$timestampID">
    <u:Created>$c</u:Created>
    <u:Expires>$e</u:Expires>
</u:Timestamp>
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

}
