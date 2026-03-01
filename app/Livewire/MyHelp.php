<?php

namespace App\Livewire;

use App\Models\HelpRequest;
use Livewire\Attributes\On;
use Livewire\Component;

class MyHelp extends Component
{
    public array $assigned = [];

    public function mount(): void
    {
        $this->loadAssigned();
    }

    #[On('refresh-my-help')]
    public function loadAssigned(): void
    {
        $this->assigned = HelpRequest::where('helper_id', auth()->id())
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(fn (HelpRequest $r) => [
                'id'                => $r->id,
                'title'             => $r->title,
                'status'            => $r->status,
                'category'          => $r->category,
                'requester_name'    => $r->user?->name ?? '—',
                'requester_contact' => $r->contact_value ?? '—',
                'contact_type'      => $r->contact_type,
                'expires_at'        => $r->expires_at->format('d M Y'),
            ])->toArray();
    }

    public function giveUp(int $id): void
    {
        $r = HelpRequest::findOrFail($id);

        if ($r->helper_id !== auth()->id()) {
            return;
        }

        $r->update(['helper_id' => null, 'status' => 'open']);
        event(new \App\Events\HelpRequestUpdated($r));
        $this->loadAssigned();
        $this->dispatch('toast', message: __('requests.given_up'), type: 'info');
        $this->dispatch('refresh-requests');
    }

    public function render()
    {
        return view('livewire.my-help');
    }
}
