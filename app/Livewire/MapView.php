<?php

namespace App\Livewire;

use App\Models\HelpRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MapView extends Component
{
    // ─── Map / area state ────────────────────────────────────
    public ?float $areaNorth = null;
    public ?float $areaSouth = null;
    public ?float $areaEast = null;
    public ?float $areaWest = null;

    // ─── Filters ─────────────────────────────────────────────
    // Note: no #[Url] — array URL-sync in Livewire v4 resets the
    // value to [] when the query param is absent, causing an empty map.
    public array $filters = ['products', 'medicine', 'transport', 'other'];

    // ─── Request form trigger coords ─────────────────────────
    public ?float $newLat = null;
    public ?float $newLng = null;
    public bool $showForm = false;

    // ─── Computed data ───────────────────────────────────────
    public array $markers = [];

    public function mount(): void
    {
        $this->loadRequests();
    }

    /**
     * Load help requests based on current filters and area.
     */
    public function loadRequests(): void
    {
        $query = HelpRequest::active()
            ->with(['user:id,name,avatar_url', 'helper:id,name']);

        if ($this->areaNorth !== null) {
            $query->inArea(
                $this->areaNorth,
                $this->areaSouth,
                $this->areaEast,
                $this->areaWest,
            );
        }

        if (empty($this->filters)) {
            // When no filters are selected, show none (user expectation)
            $this->markers = [];
            return;
        }

        $query->byCategories($this->filters);

        $requests = $query->latest()->limit(200)->get();

        $categories   = HelpRequest::categories();
        $contactTypes = HelpRequest::contactTypes();
        $statuses     = HelpRequest::statuses();

        $this->markers = $requests->map(fn (HelpRequest $r) => [
            'id' => $r->id,
            'lat' => (float) $r->latitude,
            'lng' => (float) $r->longitude,
            'title' => $r->title,
            'category' => $r->category,
            // Translated labels — used by the JS popup so language switch works
            'category_label'      => $categories[$r->category] ?? $r->category,
            'contact_type_label'  => $contactTypes[$r->contact_type] ?? $r->contact_type,
            'status_label'        => $statuses[$r->status] ?? $r->status,
            'status' => $r->status,
            'user_name' => $r->user?->name ?? 'Unknown',
            'user_avatar' => $r->user?->avatar_url,
            'contact_type' => $r->contact_type,
            'contact_value' => $r->contact_value,
            'description' => $r->description,
            'expires_at' => $r->expires_at->toIso8601String(),
            'created_at' => $r->created_at->diffForHumans(),
            'is_owner' => $r->user_id === auth()->id(),
            'helper_id' => $r->helper_id,
            'helper_name' => $r->helper?->name,
            'helper_contact' => $r->helper?->email ?? $r->contact_value,
        ])->toArray();
    }

    #[On('take-request')]
    public function takeRequest(int $requestId): void
    {
        if (! auth()->check()) {
            $this->dispatch('toast', message: __('auth.login_required'), type: 'warning');
            return;
        }

        $r = HelpRequest::findOrFail($requestId);
        if ($r->user_id === auth()->id()) {
            $this->dispatch('toast', message: __('errors.cannot_take_own_request'), type: 'warning');
            return;
        }

        $r->update(['helper_id' => auth()->id(), 'status' => 'in_progress']);
        event(new \App\Events\HelpRequestUpdated($r));
        $this->loadRequests();
        $this->dispatch('toast', message: __('requests.taken'), type: 'success');
        $this->dispatch('refresh-my-help');
    }

    #[On('done-request')]
    public function doneRequest(int $requestId): void
    {
        $r = HelpRequest::findOrFail($requestId);
        if ($r->user_id !== auth()->id()) {
            $this->dispatch('toast', message: __('errors.unauthorized'), type: 'error');
            return;
        }

        $r->update(['status' => 'fulfilled']);
        event(new \App\Events\HelpRequestUpdated($r));
        $this->loadRequests();
        $this->dispatch('toast', message: __('requests.fulfilled'), type: 'success');
        $this->dispatch('refresh-my-needs');
    }

    /**
     * Called when user draws/changes area selection on the map.
     */
    #[On('area-selected')]
    public function updateArea(float $north, float $south, float $east, float $west): void
    {
        $this->areaNorth = $north;
        $this->areaSouth = $south;
        $this->areaEast = $east;
        $this->areaWest = $west;
        $this->loadRequests();
    }

    /**
     * Clear area selection and show all requests.
     */
    public function clearArea(): void
    {
        $this->areaNorth = null;
        $this->areaSouth = null;
        $this->areaEast = null;
        $this->areaWest = null;
        $this->loadRequests();
    }

    /**
     * Called when category filter checkboxes change.
     */
    public function updatedFilters(): void
    {
        $this->loadRequests();
    }

    /**
     * Open the request form with coordinates from map click.
     */
    #[On('map-clicked')]
    public function openForm(float $lat, float $lng): void
    {
        if (! auth()->check()) {
            $this->dispatch('toast', message: __('auth.login_required'), type: 'warning');
            return;
        }

        $this->newLat = $lat;
        $this->newLng = $lng;
        $this->showForm = true;
    }

    /**
     * Close the request form.
     */
    public function closeForm(): void
    {
        $this->showForm = false;
        $this->newLat = null;
        $this->newLng = null;
    }

    /**
     * Refresh requests list (called from Echo events).
     */
    #[On('refresh-requests')]
    public function refreshRequests(): void
    {
        $this->loadRequests();
    }

    /**
     * Update status of a help request.
     */
    public function updateStatus(int $requestId, string $newStatus): void
    {
        $helpRequest = HelpRequest::findOrFail($requestId);

        if ($helpRequest->user_id !== auth()->id()) {
            $this->dispatch('toast', message: __('errors.unauthorized'), type: 'error');
            return;
        }

        $allowedStatuses = ['open', 'in_progress', 'fulfilled'];
        if (! in_array($newStatus, $allowedStatuses)) {
            return;
        }

        $helpRequest->update(['status' => $newStatus]);

        // Broadcast the update
        event(new \App\Events\HelpRequestUpdated($helpRequest));

        $this->loadRequests();

        $statusLabel = HelpRequest::statuses()[$newStatus] ?? $newStatus;
        $this->dispatch('toast', message: __('requests.status_changed', ['status' => $statusLabel]), type: 'success');
    }

    /**
     * Delete a help request.
     */
    public function deleteRequest(int $requestId): void
    {
        $helpRequest = HelpRequest::findOrFail($requestId);

        if ($helpRequest->user_id !== auth()->id()) {
            $this->dispatch('toast', message: __('errors.unauthorized'), type: 'error');
            return;
        }

        $helpRequest->delete();
        $this->loadRequests();

        $this->dispatch('toast', message: __('requests.deleted'), type: 'success');
    }

    public function render()
    {
        return view('livewire.map-view');
    }
}
