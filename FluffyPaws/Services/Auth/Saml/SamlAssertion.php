<?php

namespace FluffyPaws\Services\Auth\Saml;

/** What a valid SAML response says about the person (SamlServiceProvider::validate). */
class SamlAssertion
{
    public string $NameId = '';
    public string $NameIdFormat = '';
    /** @var array<string, string[]> attribute name => values */
    public array $Attributes = [];
    public string $AssertionId = '';
    /** Unix seconds the assertion stops being valid (for the replay guard), or null. */
    public ?int $NotOnOrAfter = null;
    /** The request this answers (SP-initiated), or null for an unsolicited (IdP-initiated) response. */
    public ?string $InResponseTo = null;

    /** First value of the first attribute present among $names, or null. */
    public function attribute(string ...$names): ?string
    {
        foreach ($names as $name) {
            $values = $this->Attributes[$name] ?? null;
            if (is_array($values) && isset($values[0]) && is_string($values[0]) && $values[0] !== '') {
                return $values[0];
            }
        }
        return null;
    }
}
