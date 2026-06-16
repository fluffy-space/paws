<?php

namespace FluffyPaws\Services\Emails;

/**
 * Registry of previewable email templates. The single extension seam for the
 * /admin/email-templates preview page: Paws registers its built-ins and any app
 * (or other package) contributes its own with one register() call at startup —
 * no edits to the controller switch or the dropdown component required.
 *
 * Registered as a singleton; renderers are pure closures invoked at request time
 * with a per-request EmailPreviewContext, so the registry holds no scoped state.
 */
class EmailPreviewRegistry
{
    /** @var array<string, array{label: string, renderer: callable}> */
    private array $templates = [];

    /**
     * @param string $key URL-safe template key used by the preview endpoint
     * @param string $label human label shown in the dropdown
     * @param callable(EmailPreviewContext): (string|object) $renderer returns the
     *        rendered HTML body, or a render Response whose ->body holds it
     */
    public function register(string $key, string $label, callable $renderer): void
    {
        $this->templates[$key] = ['label' => $label, 'renderer' => $renderer];
    }

    /** @return array<string, string> key => label, for the preview dropdown. */
    public function labels(): array
    {
        $labels = [];
        foreach ($this->templates as $key => $definition) {
            $labels[$key] = $definition['label'];
        }
        return $labels;
    }

    /** Rendered HTML body for $key (using the per-request context), or null if unknown. */
    public function render(string $key, EmailPreviewContext $context): ?string
    {
        if (!isset($this->templates[$key])) {
            return null;
        }
        $result = ($this->templates[$key]['renderer'])($context);
        return is_string($result) ? $result : $result->body;
    }
}
