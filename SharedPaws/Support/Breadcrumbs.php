<?php

namespace SharedPaws\Support;

/**
 * `BreadcrumbList` JSON-LD for a page's place in the site: Home > Help center > article.
 *
 * A stateless static helper, shaped like {@see UserDisplay}, so the help, docs and blog pages build
 * the same markup the same way on the server and in the browser. Callers emit the result through
 * `<StructuredData json="..." />` and should build it once, into a property, when the page data
 * arrives: a block that differs between SSR and hydration is a mismatch nobody looks at.
 *
 * The trail must match what the page shows: markup that describes a hierarchy the visitor cannot
 * see is the kind of structured data search engines ignore.
 */
class Breadcrumbs
{
    /**
     * @param array $trail list of [name, absolute url] pairs, from the site root down to the page.
     */
    public static function jsonLd(array $trail): string
    {
        $items = [];
        $position = 1;
        foreach ($trail as $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ];
            $position++;
        }
        return json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ]);  // no JSON_* flags: PHP constants do not exist in the transpiled JS
        // (ReferenceError in the browser); escaped slashes are still valid JSON.
    }
}
