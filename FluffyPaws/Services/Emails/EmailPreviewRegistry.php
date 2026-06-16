<?php

namespace FluffyPaws\Services\Emails;

use DotDi\DependencyInjection\Container;

/**
 * Aggregates previewable email templates for the /admin/email-templates page.
 *
 * The extension seam is the IEmailPreviewProvider interface: Paws and each app
 * register one provider binding, and this registry collects them all via
 * serviceProvider->getAll() — so adding a template never touches the controller,
 * the dropdown component, or startup wiring. Renderers are pure closures invoked
 * at request time with a per-request EmailPreviewContext; within a request the
 * collected template metadata is memoized.
 */
class EmailPreviewRegistry
{
    /** @var array<string, EmailPreviewTemplate>|null memoized key => template */
    private ?array $cache = null;

    public function __construct(private Container $container)
    {
    }

    /** @return array<string, EmailPreviewTemplate> */
    private function all(): array
    {
        if ($this->cache === null) {
            $this->cache = [];
            /** @var IEmailPreviewProvider[] $providers */
            $providers = $this->container->serviceProvider->getAll(IEmailPreviewProvider::class);
            foreach ($providers as $provider) {
                foreach ($provider->templates() as $template) {
                    $this->cache[$template->key] = $template;
                }
            }
        }
        return $this->cache;
    }

    /** @return array<string, string> key => label, for the preview dropdown. */
    public function labels(): array
    {
        $labels = [];
        foreach ($this->all() as $key => $template) {
            $labels[$key] = $template->label;
        }
        return $labels;
    }

    /** Rendered HTML body for $key (using the per-request context), or null if unknown. */
    public function render(string $key, EmailPreviewContext $context): ?string
    {
        $templates = $this->all();
        if (!isset($templates[$key])) {
            return null;
        }
        $result = ($templates[$key]->renderer)($context);
        return is_string($result) ? $result : $result->body;
    }
}
