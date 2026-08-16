<?php

namespace SharedPaws\Models\Emails;

/**
 * An email component that authors its own text/plain body.
 *
 * Every message we send is multipart/alternative, and filters compare the two parts: an HTML body
 * whose text part is a stub — or merely the subject line repeated, which is what `confirm-email`
 * and `reset-password` shipped — is the shape of phishing, and scoring tools measure the resulting
 * text ratio directly. Deriving the text from the HTML is possible but never good: the derived
 * version cannot be worded, cannot be reviewed, and silently changes whenever the layout does.
 *
 * So the text body is authored where the HTML body is authored — in the component, next to the
 * template, against the same props and the same localization keys, so the two cannot drift apart
 * unnoticed. `EmailRenderer` returns both.
 *
 * Implement this on server-rendered email components only (`$_noBrowser = true`). A component that
 * does NOT implement it still gets a text part — `EmailConnector::htmlToPlainText()` derives one as
 * a fallback — so adopting this is per-email and incremental, never a flag day.
 */
interface IPlainTextEmail
{
    /**
     * The text/plain body, already localized. Must contain any URL the HTML version links to: a
     * text part that says "click here to activate" and carries no link is the failure this exists
     * to prevent.
     */
    public function text(): string;
}
