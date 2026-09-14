<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'name'      => 'required|string|max:100',
            // 'email'     => 'required|email|max:150',
            // 'subject'   => 'required|string|max:200',
            // 'message'   => 'required|string|max:2000',

            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'not_regex:/(https?:\/\/|www\.)/i', // Prevent URLs
            ],
            'email' => [
                'required',
                'email:rfc,dns', // Validate email format and DNS records
                'max:150',
            ],
            'subject' => [
                'required',
                'string',
                'min:3',
                'max:200',
                'not_regex:/<[^>]+>/i', // Prevent HTML tags
            ],
            'message' => [
                'required',
                'string',
                'min:10',
                'max:2000',
                'not_regex:/<script/i', // Prevent script tags
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.not_regex'    => 'Name cannot contain URLs.',
            'subject.not_regex' => 'Subject cannot contain HTML.',
            'message.not_regex' => 'Message cannot contain scripts.',
            'email.email'       => 'Please provide a valid email address.',
            'name.min'          => 'Name must be at least 2 characters.',
            'message.min'       => 'Message must be at least 10 characters.',
        ];
    }
}
