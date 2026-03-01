<?php

namespace App\Events;

use App\Models\HelpRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class HelpRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public HelpRequest $helpRequest,
    ) {}

    /**
     * Get the channel the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('help-requests');
    }

    /**
     * Data to broadcast with the event.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->helpRequest->id,
            'lat' => (float) $this->helpRequest->latitude,
            'lng' => (float) $this->helpRequest->longitude,
            'category' => $this->helpRequest->category,
            'title' => $this->helpRequest->title,
            'status' => $this->helpRequest->status,
        ];
    }
}
