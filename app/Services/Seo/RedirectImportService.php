<?php

namespace App\Services\Seo;

use App\Models\SeoRedirect;
use Illuminate\Support\Facades\DB;

class RedirectImportService
{
    public function import(string $connection = 'wordpress', string $prefix = 'wpk7_'): int
    {
        $serialized = DB::connection($connection)
            ->table("{$prefix}options")
            ->where('option_name', 'wpseo-premium-redirects-export-plain')
            ->value('option_value');

        $redirects = $serialized ? @unserialize($serialized) : [];

        if (! is_array($redirects)) {
            return 0;
        }

        $count = 0;

        foreach ($redirects as $source => $redirect) {
            if (! is_array($redirect) || empty($redirect['url'])) {
                continue;
            }

            SeoRedirect::query()->updateOrCreate(
                ['source' => $this->normalizePath((string) $source)],
                [
                    'target' => $this->normalizeTarget((string) $redirect['url']),
                    'status_code' => (int) ($redirect['type'] ?? 301),
                    'is_active' => true,
                ],
            );

            $count++;
        }

        return $count;
    }

    private function normalizePath(string $value): string
    {
        return '/'.trim($value, '/');
    }

    private function normalizeTarget(string $value): string
    {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return '/'.trim($value, '/');
    }
}
