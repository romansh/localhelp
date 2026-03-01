<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\HelpRequest;

class MyHelp extends Component
{
    public array $assigned = [];

    public function mount()
    {
        $this->loadAssigned();
    }

    public function loadAssigned(): void
    {
        $this->assigned = HelpRequest::where('helper_id', auth()->id())
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'status' => $r->status,
                'requester_name' => $r->user?->name ?? null,
                'requester_contact' => $r->contact_value ?? null,
            ])->toArray();
    }

    public function render()
    {
        return view('livewire.my-help');
    }
}
