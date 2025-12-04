<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuleRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules()
    {
        return [
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'group' => ['required','in:tugasBelajar,upkp'],
            'pdf' => ['nullable','file','mimes:pdf','max:10240'],
        ];
    }
}