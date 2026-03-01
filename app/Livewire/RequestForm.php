<?php

namespace App\Livewire;

use App\Models\HelpRequest;
use App\Rules\NotSpamKeyword;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class RequestForm extends Component
{
    // ─── Location (from map click) ───────────────────────────
    public float $lat;
    public float $lng;

    // ─── Form fields ─────────────────────────────────────────
    public string $title = '';
    public string $description = '';
    public string $category = 'other';
    public string $contactType = 'telegram';
    public string $contactValue = '';
    public string $expiresIn = '24'; // hours
    public string $recaptchaToken = '';

    public function mount(float $lat, float $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;

        // Pre-fill contact from user email
        if (auth()->user()?->email) {
            $this->contactType = 'email';
            $this->contactValue = auth()->user()->email;
        }
    }

    public function rules(): array
    {
        $maxDays = config('localhelp.requests.max_expiry_days', 7);
        $maxHours = $maxDays * 24;

        return [
            'title' => ['required', 'string', 'max:100', new NotSpamKeyword],
            'description' => ['nullable', 'string', 'max:1000', new NotSpamKeyword],
            'category' => ['required', 'in:products,medicine,transport,other'],
            'contactType' => ['required', 'in:email,phone,telegram'],
            'contactValue' => ['required', 'string', 'max:255'],
            'expiresIn' => ['required', 'integer', 'min:1', "max:{$maxHours}"],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'title' => __('requests.title'),
            'description' => __('requests.description'),
            'category' => __('requests.category'),
            'contactType' => __('requests.contact_type'),
            'contactValue' => __('requests.contact_value'),
            'expiresIn' => __('requests.expires_at'),
        ];
    }

    /**
     * Submit the help request form.
     */
    public function submit(): void
    {
        // Verify reCAPTCHA (skip in local environment)
        if (! app()->isLocal() && config('localhelp.recaptcha.secret_key')) {
            $this->verifyRecaptcha();
        }

        // Check daily rate limit
        $this->checkRateLimit();

        // Validate form fields
        $this->validate();

        // Create the help request
        $helpRequest = HelpRequest::create([
            'user_id' => auth()->id(),
            'title' => $this->title,
            'description' => $this->description ?: null,
            'category' => $this->category,
            'contact_type' => $this->contactType,
            'contact_value' => $this->contactValue,
            'latitude' => $this->lat,
            'longitude' => $this->lng,
            'status' => 'open',
            'expires_at' => now()->addHours((int) $this->expiresIn),
        ]);

        // Increment daily counter
        $cacheKey = 'rate_limit:help_requests:' . auth()->id() . ':' . today()->toDateString();
        Cache::increment($cacheKey);

        // Broadcast event
        event(new \App\Events\HelpRequestCreated($helpRequest));

        // Notify parent
        $this->dispatch('request-created');
        $this->dispatch('toast', message: __('requests.created'), type: 'success');
    }

    /**
     * Check reCAPTCHA verification token.
     */
    protected function verifyRecaptcha(): void
    {
        if (empty($this->recaptchaToken)) {
            $this->addError('recaptchaToken', __('errors.recaptcha_failed'));
            return;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('localhelp.recaptcha.secret_key'),
            'response' => $this->recaptchaToken,
        ]);

        if (! $response->json('success')) {
            $this->addError('recaptchaToken', __('errors.recaptcha_failed'));
        }
    }

    /**
     * Check if user has exceeded the daily request limit.
     */
    protected function checkRateLimit(): void
    {
        $limit = config('localhelp.spam.daily_limit', 5);
        $cacheKey = 'rate_limit:help_requests:' . auth()->id() . ':' . today()->toDateString();
        $count = Cache::get($cacheKey, 0);

        if ($count >= $limit) {
            $this->addError('title', __('errors.rate_limit', ['limit' => $limit]));
        }
    }

    public function render()
    {
        return view('livewire.request-form');
    }
}
