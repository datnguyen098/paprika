<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DeepLTranslationService
{
    public function isEnabled(): bool
    {
        return setting('translation_enabled') === '1' && filled($this->apiKey());
    }

    public function usage(): array
    {
        $this->ensureConfigured();

        $response = $this->client()
            ->timeout(15)
            ->get($this->baseUrl().'/v2/usage');

        $this->throwIfFailed($response->status(), $response->json('message'));

        return [
            'character_count' => (int) $response->json('character_count', 0),
            'character_limit' => (int) $response->json('character_limit', 0),
        ];
    }

    public function translateFields(array $fields, string $targetLocale = 'en'): array
    {
        $this->ensureConfigured();

        $texts = [];
        $fieldKeys = [];

        foreach ($fields as $key => $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $texts[] = $value;
            $fieldKeys[] = $key;
        }

        if ($texts === []) {
            throw new RuntimeException('Không có nội dung tiếng Việt để dịch.');
        }

        $this->ensureUsageAvailable($texts);

        $response = $this->client()
            ->timeout(30)
            ->post($this->baseUrl().'/v2/translate', [
                'text' => $texts,
                'source_lang' => setting('deepl_source_lang') ?: 'VI',
                'target_lang' => $this->targetLanguageCode($targetLocale),
                'preserve_formatting' => true,
                'tag_handling' => 'html',
            ]);

        $this->throwIfFailed($response->status(), $response->json('message'));

        $translated = [];
        foreach ($response->json('translations', []) as $index => $item) {
            $translated[$fieldKeys[$index]] = $item['text'] ?? '';
        }

        // Auto-generate slug from translated name/title (handles both simple and full-path keys like translations[en][name])
        foreach ($translated as $key => $text) {
            if (empty($text)) {
                continue;
            }
            $slugKey = null;
            if (str_ends_with($key, '[name]')) {
                $slugKey = substr($key, 0, -6) . '[slug]';
            } elseif (str_ends_with($key, '[title]')) {
                $slugKey = substr($key, 0, -7) . '[slug]';
            } elseif ($key === 'name') {
                $slugKey = 'slug';
            } elseif ($key === 'title') {
                $slugKey = 'slug';
            }
            if ($slugKey !== null && empty($translated[$slugKey])) {
                $translated[$slugKey] = Str::slug(Str::ascii($text));
            }
        }

        return [
            'translations' => $translated,
            'usage' => $this->safeUsage(),
        ];
    }

    public function safeUsage(): ?array
    {
        try {
            return $this->usage();
        } catch (\Throwable) {
            return null;
        }
    }

    private function ensureConfigured(): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Dịch tự động chưa được bật hoặc chưa cấu hình DeepL API key.');
        }
    }

    private function ensureUsageAvailable(array $texts): void
    {
        $usage = $this->safeUsage();

        if (! $usage || ($usage['character_limit'] ?? 0) < 1) {
            return;
        }

        $requestCharacters = mb_strlen(implode('', $texts));
        $remaining = $usage['character_limit'] - $usage['character_count'];

        if ($remaining <= 0 || $requestCharacters > $remaining) {
            throw new RuntimeException('DeepL đã hết quota ký tự tháng này. Bạn có thể copy từ tiếng Việt và dịch thủ công, hoặc chờ quota tháng sau.');
        }
    }

    private function throwIfFailed(int $status, ?string $message = null): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }

        throw new RuntimeException(match ($status) {
            401, 403 => 'DeepL API key không hợp lệ hoặc chưa được kích hoạt.',
            456 => 'DeepL đã hết quota ký tự tháng này.',
            429 => 'DeepL đang giới hạn tần suất. Vui lòng thử lại sau ít phút.',
            default => $message ?: 'Không kết nối được DeepL. Vui lòng thử lại sau.',
        });
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'DeepL-Auth-Key '.$this->apiKey(),
            'Content-Type' => 'application/json',
            'User-Agent' => config('app.name', 'Paprika').'/1.0',
        ];
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders($this->headers())
            ->withOptions([
                'verify' => filter_var(config('services.deepl.verify_ssl', true), FILTER_VALIDATE_BOOLEAN),
            ]);
    }

    private function targetLanguageCode(string $locale): string
    {
        return config("locales.translation_targets.deepl.{$locale}", 'EN-US');
    }

    private function baseUrl(): string
    {
        return setting('deepl_api_plan') === 'pro'
            ? 'https://api.deepl.com'
            : 'https://api-free.deepl.com';
    }

    private function apiKey(): ?string
    {
        $value = setting('deepl_api_key');

        if (! filled($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
