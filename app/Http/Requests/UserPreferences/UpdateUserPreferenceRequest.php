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
            'categories.*' => 'exists:categories,id',
            'sources' => 'array',
            'sources.*' => 'exists:articles,source',
            'authors' => 'array',
            'authors.*' => 'exists:articles,author'
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
            $simplifiedErrors[$key] = [
                "message" => "The provided field has '".count($value)."' an invalid value".(count($value) > 1 ? "s" : '').".",
                "invalid_values" => $value
            ];
        }

        throw new HttpResponseException(response()->json([
            'message' => "There are some errors in your request",
            'errors' => $simplifiedErrors
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
