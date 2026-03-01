<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'helper_id',
        'title',
        'description',
        'category',
        'contact_type',
        'contact_value',
        'latitude',
        'longitude',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'expires_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    /**
     * Get the user who created this help request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User who agreed to help (helper)
     */
    public function helper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'helper_id');
    }

    // ─── Scopes ──────────────────────────────────────────────

    /**
     * Filter only active (non-expired, non-fulfilled) requests.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('expires_at', '>', now())
            ->where('status', '!=', 'fulfilled');
    }

    /**
     * Filter requests within a geographic bounding box.
     */
    public function scopeInArea(Builder $query, float $north, float $south, float $east, float $west): Builder
    {
        return $query
            ->whereBetween('latitude', [$south, $north])
            ->whereBetween('longitude', [$west, $east]);
    }

    /**
     * Filter requests by one or more categories.
     */
    public function scopeByCategories(Builder $query, array $categories): Builder
    {
        return $query->whereIn('category', $categories);
    }

    // ─── Helpers ─────────────────────────────────────────────

    /**
     * Check whether this request has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get available categories.
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'products' => __('requests.category_products'),
            'medicine' => __('requests.category_medicine'),
            'transport' => __('requests.category_transport'),
            'other' => __('requests.category_other'),
        ];
    }

    /**
     * Get available contact types.
     *
     * @return array<string, string>
     */
    public static function contactTypes(): array
    {
        return [
            'email' => __('requests.contact_email'),
            'phone' => __('requests.contact_phone'),
            'telegram' => __('requests.contact_telegram'),
        ];
    }

    /**
     * Get available statuses with translated labels.
     *
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'open' => __('requests.status_open'),
            'in_progress' => __('requests.status_in_progress'),
            'fulfilled' => __('requests.status_fulfilled'),
        ];
    }
}
