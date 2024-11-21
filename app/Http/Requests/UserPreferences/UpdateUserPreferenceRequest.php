<?php

namespace App\Http\Requests\UserPreferences;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categories' => 'array',
            'categories.*' => [
                'exists:categories,id',
                'distinct'
            ],
            'sources' => 'array',
            'sources.*' => [
                'exists:articles,source',
                'distinct'
            ],
            'authors' => 'array',
            'authors.*' => [
                'exists:articles,author',
                'distinct'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'categories.*.distinct' => 'Each category can only be selected once.',
            'sources.*.distinct' => 'Each source can only be selected once.',
            'authors.*.distinct' => 'Each author can only be selected once.'
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $simplifiedErrors = [];
        $arrayFieldKeysWithErrorValues = ['categories'=> [], 'sources'=>[], 'authors'=>[]];

        foreach ($errors as $errorKey => $error) {
            $keyParts = explode('.', $errorKey);
            if(count($keyParts) > 1 && in_array($keyParts[0],array_keys($arrayFieldKeysWithErrorValues))) {
                $arrayFieldKeysWithErrorValues[$keyParts[0]][] = $this->get($keyParts[0])[$keyParts[1]];
            }
        }

        foreach ($arrayFieldKeysWithErrorValues as $key => $value) {
            if (!empty($value)) {
                $simplifiedErrors[$key] = [
                    "message" => "The provided field has '".count($value)."' an invalid value".(count($value) > 1 ? "s" : '').".",
                    "invalid_values" => $value
                ];
            }
        }

        throw new HttpResponseException(response()->json([
            'message' => "There are some errors in your request",
            'errors' => $simplifiedErrors
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    public function getSummaryMessage(): string
    {
        $updatedFields = array_keys(array_filter($this->validated(), fn($value) => !empty($value)));

        if (empty($updatedFields)) {
            return 'No preferences were updated.';
        }

        $fieldNames = array_map(fn($field) => ucfirst($field), $updatedFields);
        return count($fieldNames) > 1
            ? implode(', ', array_slice($fieldNames, 0, -1)) . ' and ' . end($fieldNames) . ' preferences updated successfully.'
            : $fieldNames[0] . ' preferences updated successfully.';
    }

    public function passedValidation()
    {
        $this->merge($this->deduplicate($this->all()));
    }

    private function deduplicate(array $preferences): array
    {
        return [
            'categories' => array_values(array_unique($preferences['categories'] ?? [])),
            'sources' => array_values(array_unique($preferences['sources'] ?? [])),
            'authors' => array_values(array_unique($preferences['authors'] ?? []))
        ];
    }
}
