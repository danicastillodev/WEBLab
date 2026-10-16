<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de clientes, tanto desde el catálogo "Listado clientes"
 * como desde el modal de la nota de pago. A diferencia del propietario de la
 * historia clínica, al cliente se le exigen todos sus datos: es a quien se
 * le emite la nota de pago.
 */
class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['rfc' => strtoupper(trim((string) $this->input('rfc')))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'rfc' => ['required', 'string', 'min:12', 'max:13', Rule::unique('clientes', 'rfc')->ignore($this->route('cliente'))],
            'telefono' => ['required', 'digits:10'],
            'calle' => ['required', 'string', 'max:255'],
            'numero_exterior' => ['required', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['required', 'digits:5'],
            'estado_id' => ['required', 'integer', 'exists:estados,id'],
            'municipio_id' => [
                'required', 'integer',
                Rule::exists('municipios', 'id')->where('estado_id', $this->input('estado_id')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'rfc.unique' => 'Ya existe un cliente con ese RFC.',
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
            'codigo_postal.digits' => 'El código postal debe tener 5 dígitos.',
            'municipio_id.exists' => 'El municipio no pertenece al estado seleccionado.',
        ];
    }
}
