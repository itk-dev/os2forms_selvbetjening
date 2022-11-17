# OS2Forms Organisation

OS2Forms integrates to Digitaliseringskatalogets Organisation [sf1500](https://digitaliseringskataloget.dk/integration/sf1500).

## Calling the web service

Assuming you have your certificate in the file `certificate.p12` (or
`certificate.pfx`) you first have to extract the public certificate and the
private key.

```sh
openssl pkcs12 -in certificate.p12 -out certificate.crt -nokeys -password pass:'«the p12 password>'
openssl pkcs12 -in certificate.p12 -out certificate.priv.key -nodes -nocerts -password pass:'«the p12 password>'
```

Both the public certificate (`certificate.crt`) and the private key
(`certificate.priv.key`) are in the [PEM
format](https://en.wikipedia.org/wiki/Privacy-Enhanced_Mail).

We also need the public certificate to be on a single line:

```sh
openssl x509 -in certificate.crt -trustout | sed '1,1d;$d' | tr -d '\n'
```

Make sure to **never** commit these

## Configuration

The following must be configured in `settings.local.php`,

```phpt
// Organisation sf1500 certificates
$config['os2forms_organisation'] = [
  'public_cert_location' => 'path/to/certificate.crt',
  'priv_key_location' => 'path/to/certificate.priv.key',
];
```

here the location is relative to the
`modules/custom/os2forms_organisation` module folder.
