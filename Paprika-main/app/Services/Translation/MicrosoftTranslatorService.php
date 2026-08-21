<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MicrosoftTranslatorService
{
    public function isEnabled(): bool
    {
        return setting('translation_enabled') === '1' && filled($this->apiKey());
    }

    public function usage(): ?array
    {
        return null;
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

            $texts[] = ['Text' => $value];
            $fieldKeys[] = $key;
        }

        if ($texts === []) {
            throw new RuntimeException('Không có nội dung tiếng Việt để dịch.');
        }

        $response = Http::withHeaders($this->headers())
            ->withOptions([
                'verify' => filter_var(config('services.microsoft_translator.verify_ssl', true), FILTER_VALIDATE_BOOLEAN),
            ])
            ->timeout(30)
            ->post($this->endpoint().'/translate?api-version=3.0&from=vi&to='.$this->targetLanguageCode($targetLocale).'&textType=html', $texts);

        if (! $response->successful()) {
            $this->throwForStatus($response->status(), $response->json('error.message'));
        }

        $translated = [];
        foreach ($response->json() as $index => $item) {
            $translated[$fieldKeys[$index]] = data_get($item, 'translations.0.text', '');
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
            'usage' => null,
        ];
    }

    private function ensureConfigured(): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Microsoft Translator chưa được bật hoặc chưa cấu hình API key.');
        }
    }

    private function throwForStatus(int $status, ?string $message = null): never
    {
        throw new RuntimeException(match ($status) {
            401, 403 => 'Microsoft Translator API key/region không hợp lệ hoặc chưa được kích hoạt.',
            429 => 'Microsoft Translator đang giới hạn tần suất hoặc đã hết quota miễn phí. Vui lòng kiểm tra Azure Portal.',
            default => $message ?: 'Không kết nối được Microsoft Translator. Vui lòng thử lại sau.',
        });
    }

    private function headers(): array
    {
        $headers = [
            'Ocp-Apim-Subscription-Key' => $this->apiKey(),
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-ClientTraceId' => (string) Str::uuid(),
        ];

        if (filled(setting('microsoft_translator_region'))) {
            $headers['Ocp-Apim-Subscription-Region'] = setting('microsoft_translator_region');
        }

        return $headers;
    }

    private function endpoint(): string
    {
        return rtrim(setting('microsoft_translator_endpoint') ?: 'https://api.cognitive.microsofttranslator.com', '/');
    }

    private function targetLanguageCode(string $locale): string
    {
        return config("locales.translation_targets.microsoft.{$locale}", 'en');
    }

    private function apiKey(): ?string
    {
        $value = setting('microsoft_translator_key');

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
