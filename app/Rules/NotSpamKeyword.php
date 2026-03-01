<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotSpamKeyword implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $blacklist = config('localhelp.spam.blacklist', []);
        $lower = mb_strtolower($value);

        foreach ($blacklist as $word) {
            if (str_contains($lower, mb_strtolower($word))) {
                $fail(__('errors.spam_detected'));
                return;
            }
        }
    }
}
