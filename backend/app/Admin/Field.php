<?php

namespace App\Admin;

use App\Support\Icons;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * One form field of an admin resource. Fluent:
 *   Field::text('title')->required()->max(120)->hint('Shown in bold')
 *
 * Types: text, textarea, markdown, image, link (URL or /path), email, number, date, toggle, select, icon.
 */
final class Field
{
    public string $label;

    public bool $required = false;

    public bool $readonly = false;

    public bool $wide = false;

    public ?string $hint = null;

    public ?string $placeholder = null;

    public ?int $max = null;

    public int $rows = 3;

    public mixed $default = null;

    /** @var array<int|string, string>|Closure(?Model): array<int|string, string> */
    public array|Closure $options = [];

    public ?string $emptyOption = null;

    /** @var list<mixed> */
    public array $extraRules = [];

    private function __construct(public readonly string $type, public readonly string $name, ?string $label = null)
    {
        $this->label = $label ?? Str::of($name)->replace('_', ' ')->ucfirst()->toString();
        $this->wide = in_array($type, ['textarea', 'markdown', 'image'], true);
        $this->rows = $type === 'markdown' ? 10 : 3;
    }

    public static function text(string $name, ?string $label = null): self { return new self('text', $name, $label); }

    public static function textarea(string $name, ?string $label = null): self { return new self('textarea', $name, $label); }

    public static function markdown(string $name, ?string $label = null): self { return new self('markdown', $name, $label); }

    public static function image(string $name = 'image', ?string $label = null): self { return new self('image', $name, $label); }

    public static function link(string $name, ?string $label = null): self { return (new self('link', $name, $label))->placeholder('https://… or /page'); }

    public static function email(string $name, ?string $label = null): self { return new self('email', $name, $label); }

    public static function number(string $name, ?string $label = null): self { return (new self('number', $name, $label))->default(0); }

    public static function date(string $name, ?string $label = null): self { return new self('date', $name, $label); }

    public static function toggle(string $name, ?string $label = null): self { return (new self('toggle', $name, $label))->default(false); }

    public static function select(string $name, ?string $label = null): self { return new self('select', $name, $label); }

    public static function icon(string $name = 'icon', ?string $label = null): self
    {
        return (new self('icon', $name, $label))->options(Icons::OPTIONS);
    }

    public function label(string $label): self { $this->label = $label; return $this; }

    public function required(bool $required = true): self { $this->required = $required; return $this; }

    public function readonly(bool $readonly = true): self { $this->readonly = $readonly; return $this; }

    public function wide(bool $wide = true): self { $this->wide = $wide; return $this; }

    public function hint(?string $hint): self { $this->hint = $hint; return $this; }

    public function placeholder(?string $placeholder): self { $this->placeholder = $placeholder; return $this; }

    public function max(int $max): self { $this->max = $max; return $this; }

    public function rows(int $rows): self { $this->rows = $rows; return $this; }

    public function default(mixed $value): self { $this->default = $value; return $this; }

    /** @param array<int|string, string>|Closure(?Model): array<int|string, string> $options */
    public function options(array|Closure $options, ?string $emptyOption = null): self
    {
        $this->options = $options;
        $this->emptyOption = $emptyOption;

        return $this;
    }

    public function rules(mixed ...$rules): self { $this->extraRules = array_merge($this->extraRules, $rules); return $this; }

    /** @return array<int|string, string> */
    public function resolveOptions(?Model $record = null): array
    {
        return $this->options instanceof Closure ? ($this->options)($record) : $this->options;
    }

    public function isUpload(): bool
    {
        return $this->type === 'image';
    }

    /** Validation rules for data.{name}. */
    public function validationRules(?Model $record = null): array
    {
        $presence = $this->required ? 'required' : 'nullable';

        $rules = match ($this->type) {
            'toggle' => ['boolean'],
            'number' => [$presence, 'integer', 'min:0', 'max:'.($this->max ?? 1000000)],
            'date' => [$presence, 'date'],
            'email' => [$presence, 'email', 'max:'.($this->max ?? 255)],
            // Absolute http(s) URL, site path, #anchor, mailto: or tel:.
            'link' => [$presence, 'string', 'max:'.($this->max ?? 500), 'regex:/^(https?:\/\/[^\s]+|\/[^\s]*|#[^\s]*|mailto:[^\s]+|tel:[^\s]+)$/i'],
            'select', 'icon' => [$presence, Rule::in(array_map('strval', array_keys($this->resolveOptions($record))))],
            'textarea' => [$presence, 'string', 'max:'.($this->max ?? 5000)],
            'markdown' => [$presence, 'string', 'max:'.($this->max ?? 100000)],
            default => [$presence, 'string', 'max:'.($this->max ?? 255)],
        };

        return array_merge($rules, $this->extraRules);
    }

    /** Rules for uploads.{name}. */
    public function uploadRules(): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'];
    }

    /** Value for the form (strings for inputs, bool for toggles). */
    public function formValue(mixed $value): mixed
    {
        return match ($this->type) {
            'toggle' => (bool) ($value ?? $this->default),
            'number' => (string) ($value ?? $this->default ?? 0),
            'date' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) ($value ?? ''),
            default => $value === null ? (string) ($this->default ?? '') : (string) $value,
        };
    }

    /** Value to store from the validated form value. */
    public function storeValue(mixed $value): mixed
    {
        return match ($this->type) {
            'toggle' => (bool) $value,
            'number' => (int) ($value ?: 0),
            default => ($value === '' || $value === null) ? null : (is_string($value) ? trim($value) : $value),
        };
    }
}
