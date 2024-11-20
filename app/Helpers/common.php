<?php

if (!function_exists('extract_domain')) {
    function extract_domain(string $url): string
    {
        $domain = parse_url($url, PHP_URL_HOST);
        return str_replace('www.', '', $domain ?? '');
    }
}
if (!function_exists('getNestedValue')) {
    function getNestedValue(array $data, string|array $keys, ?callable $processor = null): mixed
    {
        if (is_string($keys)) {
            $keys = explode('|', $keys);
        }

        foreach ($keys as $key) {
            $value = data_get($data, $key);
            if ($value !== null) {
                return $processor ? $processor($value) : $value;
            }
        }

        return null;
    }
}
