<?php

namespace App\Http\Requests\ShippingAddress;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class UpdateShippingAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['sometimes', 'required', 'string', 'max:100'],
            'zip_code' => ['sometimes', 'required', 'string', 'max:20'],
            'country' => ['sometimes', 'required', 'string', 'max:100'],
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address.required' => 'La dirección no puede estar vacía.',
            'city.required' => 'La ciudad no puede estar vacía.',
            'state.required' => 'El estado, departamento o provincia no puede estar vacío.',
            'zip_code.required' => 'El código postal no puede estar vacío.',
            'country.required' => 'El país no puede estar vacío.',
        ];
    }

    /**
     * Handle a failed validation attempt according to API spec.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
