<?php

namespace Pupils\Components\Views\Shared\StructuredData;

use Viewi\Components\BaseComponent;

/**
 * Renders a JSON-LD block.
 *
 * THE WHOLE `<script>` TAG is built in PHP and emitted as raw HTML, rather than the tag living in
 * the template with the JSON interpolated into it. Two Viewi constraints force that shape:
 *
 *  - JSON is made of `{` and `"`, and a template reads `{` as the start of an expression, so the
 *    document can never be written inline;
 *  - `{{ }}` (raw, unescaped) works inside an ordinary element — it is how the QR preview injects
 *    its SVG — but NOT inside `<script>`, where the value comes out HTML-escaped with the braces
 *    left as literal text. `&quot;` inside a script is not decoded by any consumer, so that block
 *    is silently unparseable.
 *
 * Hence: a hidden wrapper element, raw HTML inside it, script tag and all. JSON-LD is valid
 * anywhere in the document, so being in the body rather than the head costs nothing.
 *
 * LIVES IN PUPILS, NOT IN AN APP. Nothing here is site-specific — it takes a finished JSON document
 * and wraps it — and every Paws site wants the same thing. It is referenced only as a TEMPLATE TAG
 * (`<StructuredData json="{...}" />`), never imported in PHP, so app templates that used it while
 * it lived in `Components\Views\Common` keep working unchanged: Viewi resolves component tags by
 * global short name.
 *
 * WHAT IS WORTH MARKING UP (2026): `Organization` and `WebSite` for the entity and its logo,
 * `BreadcrumbList`, `BlogPosting`, and `SoftwareApplication` with `offers` so the price can appear.
 * `FAQPage` deliberately is NOT here: Google restricted FAQ rich results to government and health
 * sites in 2023, so marking up our FAQ blocks would render nothing.
 */
class StructuredData extends BaseComponent
{
    /** A complete JSON document. Empty renders nothing. */
    public string $json = '';

    /** The full script element, because the tag cannot live in the template (see above). */
    public function html(): string
    {
        if ($this->json === '') {
            return '';
        }
        return '<script type="application/ld+json">' . $this->json . '</script>';
    }
}
