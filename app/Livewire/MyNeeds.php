<?php

namespace App\Livewire;

use App\Models\HelpRequest;
use Livewire\Attributes\On;
use Livewire\Component;

class MyNeeds extends Component
{
    public array $requests = [];

    public function mount(): void
    {
        $this->loadRequests();
    }

    #[On('refresh-my-needs')]
    public function loadRequests(): void
    {
        $this->requests = HelpRequest::where('user_id', auth()->id())
            ->with('helper:id,name,email')
            ->latest()
            ->get()
            ->map(fn (HelpRequest $r) => [
                'id'             => $r->id,
                'title'          => $r->title,
                'status'         => $r->status,
                'helper_name'    => $r->helper?->name ?? null,
                'helper_contact' => $r->helper?->email ?? null,
            ])->toArray();
    }

    public function markDone(int $id): void
    {
        $r = HelpRequest::findOrFail($id);

        if ($r->user_id !== auth()->id()) {
            return;
        }

        $r->update(['status' => 'fulfilled']);
        event(new \App\Events\HelpRequestUpdated($r));
        $this->loadRequests();
        $this->dispatch('toast', message: __('requests.fulfilled'), type: 'success');
        $this->dispatch('refresh-requests');
    }

    public function render()
    {
        return view('livewire.my-needs');
    }
}
