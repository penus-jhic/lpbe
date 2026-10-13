<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActivityCategory;
use App\Http\Requests\Concerns\DecodesJsonFields;
use App\Models\Activity;
use App\Rules\ArticleBlock;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Used for both creating and updating Talent Day classes and extracurricular clubs, the form always sends every field.
 * The detail page body is optional: an empty list shows only the description.
 */
class ActivityRequest extends FormRequest
{
    use DecodesJsonFields;

    /**
     * Every account is an administrator, so being authenticated (enforced by the route) is enough.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->decodeJsonFields(['body']);

        $activity = $this->route('activity');
        $slug = $this->filled('slug')
            ? Str::slug($this->string('slug')->toString())
            : Activity::uniqueSlug($this->string('title')->toString(), $activity?->id);

        $this->merge(['slug' => $slug]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ActivityCategory::class)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('activities', 'slug')->ignore($this->route('activity'))],
            'description' => ['required', 'string', 'max:1000'],
            'icon' => ['required', 'string', 'max:50', 'alpha_dash'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'body' => ['present', 'array', 'max:100'],
            'body.*' => [new ArticleBlock],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}
