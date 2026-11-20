<?php
declare(strict_types=1);
namespace App\Core;

/** Tiny rule-based validator. Rules: required|string|numeric|integer|email|min:n|gte:n|gt:n|min_len:n */
final class Validator
{
    private array $errors = [];

    private function __construct(private array $data, private array $rules) {}

    public static function make(array $data, array $rules): self
    {
        $v = new self($data, $rules);
        $v->run();
        return $v;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleset) {
            $rules = is_array($ruleset) ? $ruleset : explode('|', $ruleset);
            $value = $this->data[$field] ?? null;
            $has = isset($this->data[$field]) && $this->data[$field] !== '';

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $label = ucfirst(str_replace('_', ' ', $field));
                switch ($name) {
                    case 'required': if (!$has) $this->add($field, "$label is required."); break;
                    case 'numeric':  if ($has && !is_numeric($value)) $this->add($field, "$label must be a number."); break;
                    case 'integer':  if ($has && filter_var($value, FILTER_VALIDATE_INT) === false) $this->add($field, "$label must be an integer."); break;
                    case 'email':    if ($has && !filter_var($value, FILTER_VALIDATE_EMAIL)) $this->add($field, "$label must be a valid email."); break;
                    case 'gt':       if ($has && (float) $value <= (float) $param) $this->add($field, "$label must be greater than $param."); break;
                    case 'gte':      if ($has && (float) $value < (float) $param) $this->add($field, "$label must be at least $param."); break;
                    case 'min_len':  if ($has && mb_strlen((string) $value) < (int) $param) $this->add($field, "$label must be at least $param characters."); break;
                }
            }
        }
    }

    private function add(string $field, string $msg): void { $this->errors[$field][] = $msg; }

    public function passes(): bool { return $this->errors === []; }
    public function fails(): bool  { return !$this->passes(); }
    public function errors(): array { return $this->errors; }
    public function firstErrors(): array { return array_map(fn ($e) => $e[0], $this->errors); }
}
