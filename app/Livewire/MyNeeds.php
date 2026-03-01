<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\HelpRequest;

class MyNeeds extends Component
{
    public array $requests = [];

    public function mount()
    {
        $this->loadRequests();
    }

    public function loadRequests(): void
    {
        $this->requests = HelpRequest::where('user_id', auth()->id())
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'status' => $r->status,
                'helper_name' => $r->helper?->name ?? null,
                'helper_contact' => $r->helper?->contact_value ?? null,
            ])->toArray();
    }

    public function render()
    {
        return view('livewire.my-needs');
    }
}
