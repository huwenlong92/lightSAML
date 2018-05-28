<?php

namespace LightSaml\Tests\Functional\Model\Assertion;

use LightSaml\ClaimTypes;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Model\Assertion\EncryptedAssertionReader;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Protocol\Response;
use LightSaml\Tests\BaseTestCase;

class EncryptedAssertionReaderTest extends BaseTestCase
{
    public function test_decrypt()
    {
        $xml = <<<EOT
<?xml version="1.0"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="_973220eb0b94e0367859487a8135e7855742ae2431" InResponseTo="_981d6909d57a6131e98da42ac76720776bd2a59d25" Version="2.0" IssueInstant="2015-09-28T07:24:17Z" Destination="https://localhost/lightsaml/lightSAML/web/sp/acs.php"><saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" Format="urn:oasis:names:tc:SAML:2.0:nameid-format:entity">https://lightsaml.local/idp</saml:Issuer><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
    <ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
  <ds:Reference URI="#_973220eb0b94e0367859487a8135e7855742ae2431"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/><ds:DigestValue>FKXI2BoZn0ix6Yc5m3QM3PDV8dQ=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>o6redfiU43TO5s0RtHUj9R0PSZVJryAs1e39biVOm84Xrd/n9IKCui3vWd9bN/wBAD9/ZZ4b48fMKfLI0hRivNEi9yJZb91uavdU1StjgpckdZtWdt315zf1+p4+xqnFAtDMWcTP3V8XAGuGfBUT+VndsS7VHVjzSjCj6+qC123TBpJ7HvC9sFUbH+uXgJaK71so8b3z79VH3C26Qnly3bmmARLkNZL8bnwlHJA/BrG/kJN5Lgv6tKB6xRbYU0grSGsA1Vt/nk2bpIGYPZU3SOIVVLUoHTkA6gGceKyJNqPcfJQVNpljTxqZjsJy7mZF9coWBSbTr5DRiGjd9pFOUA==</ds:SignatureValue>
<ds:KeyInfo><ds:X509Data><ds:X509Certificate>MIIDyjCCArKgAwIBAgIJAJNOFuQd727cMA0GCSqGSIb3DQEBBQUAMEwxCzAJBgNVBAYTAlJTMREwDwYDVQQIEwhCZWxncmFkZTESMBAGA1UEChMJTGlnaHRTQU1MMRYwFAYDVQQDEw1saWdodHNhbWwuY29tMB4XDTE1MDkxMzE5MDE0MFoXDTI1MDkxMDE5MDE0MFowTDELMAkGA1UEBhMCUlMxETAPBgNVBAgTCEJlbGdyYWRlMRIwEAYDVQQKEwlMaWdodFNBTUwxFjAUBgNVBAMTDWxpZ2h0c2FtbC5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC7pUKOPMyE2oScHLPGJFTepK9j1H03e/s/WnONw8ZwYBaBIYIQuX6uE8jFPdD0uQSaYpOw5h5Tgq6xBV7m2kPO53hs8gEGWRbCdCtxi9EMJwIOYr+isG0N+DvV9KybJf6tqcM50PiFjVNtfx8IubMpAKCbquaqdLaHH0rgP1hbgnGm5YZkyEK4s8xuLUDS6qL7N7a/ez2Zk45u3L3qFcuncPI5BTnJg6fqlypDhCDOBI5Ljw10HmgZHPIXzOhEPVV+rX2iHhF4V9vzEoeIUABYXQVNRRNHpPdVsK6iTTkyvbrGJ/tv3oFZhNOSL0Kuy+Q9nlE9fEFqyUydJ67vsXqZAgMBAAGjga4wgaswHQYDVR0OBBYEFHPT6Ey1qgxMzMIt2d3OWuwzfPSUMHwGA1UdIwR1MHOAFHPT6Ey1qgxMzMIt2d3OWuwzfPSUoVCkTjBMMQswCQYDVQQGEwJSUzERMA8GA1UECBMIQmVsZ3JhZGUxEjAQBgNVBAoTCUxpZ2h0U0FNTDEWMBQGA1UEAxMNbGlnaHRzYW1sLmNvbYIJAJNOFuQd727cMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADggEBAHkHtwJBoeOhvr06M0MikKc99ze6TqAGvf+QkgFoV1sWGAh3NKcAR+XSlfK+sQWrHGkiia5hWKgAPMMUbkLP9DFWkjbK241isCZZD/LvA1anbV+7Pidn+swZ5dR7ynX2vj0kFYb+VsGPkavNcj8RN/DduhN/Tmi5sQAlWhaw06UAeEqXtFeLbTgLffBaj7PmR0IYjvTZA0X2FdRu0GXRxn7zghjpvSq9nuWa3pGbfdVtL6GIkwYUPcDzjr4OeGXNmIZe/wMCnz6VGZY+LUgzi/4DAC6V3OjMuhdqS/2+o1+CXCwN08CIHQV6+AUBenEVawMsiadLBgx3kFe5iXrYRMA=</ds:X509Certificate></ds:X509Data></ds:KeyInfo></ds:Signature><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
    <ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
  <ds:Reference URI="#_973220eb0b94e0367859487a8135e7855742ae2431"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/><ds:DigestValue>yo6ajbd+5N4zfH1IK+Up21KDuQw=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>ETYMDZA7IzajOPOqxrLjQImiEhC92u3k3ICoeytaI2KtLRK76hsWtNGIABvSAERUaCpHq+Uzit3yxTTXnCz1lHNzhKL27i42YwbMUe5IWRUYCVk1fJVrAcjWYYsnFMeBq7KRP8a5fHeg9PcIAZoEVz48DOUyx+kSArv2eF8B07fayu2Xp6fVGlJHAOcFWh6mK9ahLhEO3u4cLlvzVH0djF3jsY/qcH6xSK+dXu3JIgo84iJCIVayjxHbYYWA85/gnanODQ+t6cQmVqUztTfgebORgJ+PCXi5FxLPgSJM/PzO/uQ5TavKNuG3rmjjc9nHEYTrdFQ2OOU/gkLi+y31Iw==</ds:SignatureValue>
<ds:KeyInfo><ds:X509Data><ds:X509Certificate>MIIDyjCCArKgAwIBAgIJAJNOFuQd727cMA0GCSqGSIb3DQEBBQUAMEwxCzAJBgNVBAYTAlJTMREwDwYDVQQIEwhCZWxncmFkZTESMBAGA1UEChMJTGlnaHRTQU1MMRYwFAYDVQQDEw1saWdodHNhbWwuY29tMB4XDTE1MDkxMzE5MDE0MFoXDTI1MDkxMDE5MDE0MFowTDELMAkGA1UEBhMCUlMxETAPBgNVBAgTCEJlbGdyYWRlMRIwEAYDVQQKEwlMaWdodFNBTUwxFjAUBgNVBAMTDWxpZ2h0c2FtbC5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC7pUKOPMyE2oScHLPGJFTepK9j1H03e/s/WnONw8ZwYBaBIYIQuX6uE8jFPdD0uQSaYpOw5h5Tgq6xBV7m2kPO53hs8gEGWRbCdCtxi9EMJwIOYr+isG0N+DvV9KybJf6tqcM50PiFjVNtfx8IubMpAKCbquaqdLaHH0rgP1hbgnGm5YZkyEK4s8xuLUDS6qL7N7a/ez2Zk45u3L3qFcuncPI5BTnJg6fqlypDhCDOBI5Ljw10HmgZHPIXzOhEPVV+rX2iHhF4V9vzEoeIUABYXQVNRRNHpPdVsK6iTTkyvbrGJ/tv3oFZhNOSL0Kuy+Q9nlE9fEFqyUydJ67vsXqZAgMBAAGjga4wgaswHQYDVR0OBBYEFHPT6Ey1qgxMzMIt2d3OWuwzfPSUMHwGA1UdIwR1MHOAFHPT6Ey1qgxMzMIt2d3OWuwzfPSUoVCkTjBMMQswCQYDVQQGEwJSUzERMA8GA1UECBMIQmVsZ3JhZGUxEjAQBgNVBAoTCUxpZ2h0U0FNTDEWMBQGA1UEAxMNbGlnaHRzYW1sLmNvbYIJAJNOFuQd727cMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADggEBAHkHtwJBoeOhvr06M0MikKc99ze6TqAGvf+QkgFoV1sWGAh3NKcAR+XSlfK+sQWrHGkiia5hWKgAPMMUbkLP9DFWkjbK241isCZZD/LvA1anbV+7Pidn+swZ5dR7ynX2vj0kFYb+VsGPkavNcj8RN/DduhN/Tmi5sQAlWhaw06UAeEqXtFeLbTgLffBaj7PmR0IYjvTZA0X2FdRu0GXRxn7zghjpvSq9nuWa3pGbfdVtL6GIkwYUPcDzjr4OeGXNmIZe/wMCnz6VGZY+LUgzi/4DAC6V3OjMuhdqS/2+o1+CXCwN08CIHQV6+AUBenEVawMsiadLBgx3kFe5iXrYRMA=</ds:X509Certificate></ds:X509Data></ds:KeyInfo></ds:Signature><samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status><saml:EncryptedAssertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"><xenc:EncryptedData xmlns:xenc="http://www.w3.org/2001/04/xmlenc#" xmlns:dsig="http://www.w3.org/2000/09/xmldsig#" Type="http://www.w3.org/2001/04/xmlenc#Element"><xenc:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes128-cbc"/><dsig:KeyInfo xmlns:dsig="http://www.w3.org/2000/09/xmldsig#"><xenc:EncryptedKey><xenc:EncryptionMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/><xenc:CipherData><xenc:CipherValue>eBXzY5t2TX8r2uNK3aO+4w4K26kGTMgYUaUL22CI4Ntb4Y2tPvenP0R/ncf0GLUXcfwtLLq9dXfV+PI0fucdu9lSZ2yqjj63aBMMZUlxtKA0WXAOI7JX0kj8TG8PFOau+ByLOlUT1oxibCcNT/Xae6YS2muvR3oM3ADn5EOEVKx5Ubzo8WoKxDBjEAluzruikc6gkyoWRexnUlYuhm0XaAnzDz8+9qYIriRoAk+wxmD8eJ6WwRcdahIpCotJ2LaJ/SGmp388x8l6C5G+ITxe5fJScQpUr1bb/UKL3r6mV9NMF0yAe2LfqlJLHQ3iYCcJKRsn59CmLPH1ku+8yd1low==</xenc:CipherValue></xenc:CipherData></xenc:EncryptedKey></dsig:KeyInfo>
   <xenc:CipherData>
      <xenc:CipherValue>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</xenc:CipherValue>
   </xenc:CipherData>
</xenc:EncryptedData></saml:EncryptedAssertion></samlp:Response>

EOT;

        $deserializationContext = new DeserializationContext();
        $deserializationContext->getDocument()->loadXML($xml);

        $response = new Response();
        $response->deserialize($deserializationContext->getDocument(), $deserializationContext);

        $credential = new X509Credential(
            X509Certificate::fromFile(__DIR__.'/../../../../../../resources/sample/Certificate/lightsaml-idp.crt'),
            KeyHelper::createPrivateKey(__DIR__.'/../../../../../../resources/sample/Certificate/lightsaml-idp.key', '', true)
        );

        $decryptDeserializeContext = new DeserializationContext();

        /** @var EncryptedAssertionReader $reader */
        $reader = $response->getFirstEncryptedAssertion();
        $assertion = $reader->decryptMultiAssertion([$credential], $decryptDeserializeContext);

        $this->assertEquals('_c9cbe081e1b1294c9ea31d98f4a473a081466502a0', $assertion->getId());
        $this->assertEquals('https://lightsaml.local/idp', $assertion->getIssuer()->getValue());
        $this->assertEquals('name@id.com', $assertion->getSubject()->getNameID()->getValue());

        $this->assertEquals('common-name', $assertion->getFirstAttributeStatement()->getFirstAttributeByName(ClaimTypes::COMMON_NAME)->getFirstAttributeValue());
        $this->assertEquals('somebody@example.com', $assertion->getFirstAttributeStatement()->getFirstAttributeByName(ClaimTypes::EMAIL_ADDRESS)->getFirstAttributeValue());
    }
}
