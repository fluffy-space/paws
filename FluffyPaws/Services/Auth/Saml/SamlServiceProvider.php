<?php

namespace FluffyPaws\Services\Auth\Saml;

use OneLogin\Saml2\AuthnRequest;
use OneLogin\Saml2\Constants;
use OneLogin\Saml2\IdPMetadataParser;
use OneLogin\Saml2\Response;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Utils;

/**
 * SAML 2.0 browser sign-in as a service provider, around onelogin/php-saml. The only class that
 * talks to the library, so it can be replaced and tests can drive it with their own signed
 * responses. App-neutral: the app decides which IdP, where the ACS is and what an assertion means.
 *
 * Strict by default: signed assertion or signed response required (the library refuses unsigned
 * ones), schema validation, Destination must match the ACS exactly, Audience must be our entity
 * ID, NotBefore / NotOnOrAfter enforced, an InResponseTo we did not expect is refused. Replay
 * protection is the caller's: store AssertionId until NotOnOrAfter.
 *
 * php-saml reads the current URL from $_SERVER and its own static base URL. Under Swoole those are
 * not this request's, so validate() sets them to the ACS just before checking and puts $_SERVER
 * back (the base URL is the app's own origin, the same for every request).
 * The check does no I/O, so no other coroutine runs in between.
 *
 * Config keys: spEntityId, acsUrl, idpEntityId, idpSsoUrl, idpCerts (PEM strings, two during a
 * rollover).
 */
class SamlServiceProvider
{
    public function __construct(private array $config) {}

    public function settings(bool $spOnly = false): Settings
    {
        $certs = array_values(array_filter(array_map(fn($c) => self::certBody((string) $c), $this->config['idpCerts'] ?? [])));
        $settings = [
            'strict' => true,
            'debug' => false,
            'sp' => [
                'entityId' => (string) $this->config['spEntityId'],
                'assertionConsumerService' => ['url' => (string) $this->config['acsUrl'], 'binding' => Constants::BINDING_HTTP_POST],
                'NameIDFormat' => Constants::NAMEID_UNSPECIFIED,
            ],
            'idp' => [
                'entityId' => (string) ($this->config['idpEntityId'] ?? ''),
                'singleSignOnService' => ['url' => (string) ($this->config['idpSsoUrl'] ?? ''), 'binding' => Constants::BINDING_HTTP_REDIRECT],
                'x509certMulti' => ['signing' => $certs],
            ],
            'security' => [
                'authnRequestsSigned' => false,
                'wantMessagesSigned' => false,
                'wantAssertionsSigned' => false,
                'wantAssertionsEncrypted' => false,
                'wantNameId' => true,
                'requestedAuthnContext' => false,
                'wantXMLValidation' => true,
                'destinationStrictlyMatches' => true,
                'rejectUnsolicitedResponsesWithInResponseTo' => true,
                'relaxDestinationValidation' => false,
                'allowRepeatAttributeName' => true,
            ],
        ];
        return new Settings($settings, $spOnly);
    }

    /**
     * An SP-initiated sign-in: the IdP URL to send the browser to (HTTP-Redirect binding) and the
     * request id the answer must carry back as InResponseTo.
     * @return array{url: string, id: string}
     */
    public function authnRequest(?string $relayState): array
    {
        $request = new AuthnRequest($this->settings(), false, false, false);
        $query = ['SAMLRequest' => $request->getRequest()];
        if ($relayState !== null && $relayState !== '') {
            $query['RelayState'] = $relayState;
        }
        $url = (string) $this->config['idpSsoUrl'];
        return [
            'url' => $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            'id' => $request->getId(),
        ];
    }

    /**
     * Check a posted SAMLResponse. $requestId: the id of the request this browser started, or null
     * to accept only an unsolicited response. Returns the assertion, or a short reason (never the
     * response itself: it may hold personal data).
     */
    public function validate(string $samlResponse, ?string $requestId): SamlAssertion|string
    {
        $acs = (string) $this->config['acsUrl'];
        $path = (string) (parse_url($acs, PHP_URL_PATH) ?? '/');
        $saved = [];
        foreach (['REQUEST_URI', 'SCRIPT_NAME', 'QUERY_STRING', 'PATH_INFO', 'HTTP_HOST', 'HTTPS', 'SERVER_PORT'] as $key) {
            $saved[$key] = $_SERVER[$key] ?? null;
        }
        try {
            Utils::setBaseURL(rtrim((string) preg_replace('#^(https?://[^/]+).*$#', '$1', $acs), '/') . '/');
            $_SERVER['REQUEST_URI'] = $path;
            $_SERVER['SCRIPT_NAME'] = $path;
            unset($_SERVER['QUERY_STRING'], $_SERVER['PATH_INFO']);
            $response = new Response($this->settings(), $samlResponse);
            if (!$response->isValid($requestId)) {
                return 'invalid: ' . self::reason((string) $response->getError(false));
            }
            $assertion = new SamlAssertion();
            $assertion->NameId = (string) $response->getNameId();
            $assertion->NameIdFormat = (string) $response->getNameIdFormat();
            $assertion->Attributes = $response->getAttributes();
            $assertion->AssertionId = (string) $response->getAssertionId();
            $assertion->NotOnOrAfter = $response->getAssertionNotOnOrAfter();
            $root = $response->getXMLDocument()->documentElement;
            $assertion->InResponseTo = $root->hasAttribute('InResponseTo') ? $root->getAttribute('InResponseTo') : null;
            return $assertion;
        } catch (\Throwable $e) {
            return 'invalid: ' . self::reason($e->getMessage());
        } finally {
            // The library's static base URL stays: it is this app's own origin for every request.
            foreach ($saved as $key => $value) {
                if ($value === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $value;
                }
            }
        }
    }

    /** Our SP metadata XML, for the IdP admin to upload. */
    public function metadata(): string
    {
        return $this->settings(true)->getSPMetadata();
    }

    /**
     * An IdP metadata document: entity ID, the HTTP-Redirect SSO URL and the signing certificates.
     * @return array{entityId: string, ssoUrl: string, certs: string[]}|null
     */
    public static function parseIdpMetadata(string $xml): ?array
    {
        try {
            $parsed = IdPMetadataParser::parseXML($xml);
        } catch (\Throwable $e) {
            return null;
        }
        $idp = $parsed['idp'] ?? null;
        if (!is_array($idp) || empty($idp['entityId']) || empty($idp['singleSignOnService']['url'])) {
            return null;
        }
        $certs = [];
        if (!empty($idp['x509cert'])) {
            $certs[] = (string) $idp['x509cert'];
        }
        foreach ($idp['x509certMulti']['signing'] ?? [] as $cert) {
            $certs[] = (string) $cert;
        }
        return [
            'entityId' => (string) $idp['entityId'],
            'ssoUrl' => (string) $idp['singleSignOnService']['url'],
            'certs' => array_values(array_unique(array_map(fn($c) => self::certBody($c), $certs))),
        ];
    }

    /** The base64 body of a PEM certificate (header, footer and whitespace removed). */
    public static function certBody(string $pem): string
    {
        return (string) preg_replace('/-----(BEGIN|END) CERTIFICATE-----|\s+/', '', $pem);
    }

    /** A log-safe reason: the library's message without anything after a colon that could echo values. */
    private static function reason(string $message): string
    {
        return mb_substr(trim($message), 0, 160);
    }
}
