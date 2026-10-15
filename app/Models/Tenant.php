<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['admin_id', 'subdomain', 'name'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    public static function rootDomain(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($host) ? $host : 'localhost';
    }

    public function url(): string
    {
        $baseUrl = parse_url((string) config('app.url'));
        $scheme = $baseUrl['scheme'] ?? 'http';
        $port = isset($baseUrl['port']) ? ':'.$baseUrl['port'] : '';

        return $scheme.'://'.$this->subdomain.'.'.static::rootDomain().$port;
    }

    /**
     * A club is awaiting payment until its registration team is confirmed.
     */
    public function isAwaitingPayment(): bool
    {
        $clubTeams = $this->teams()->where('is_personal', false);

        return (clone $clubTeams)->where('payment_status', 'pending')->exists()
            && (clone $clubTeams)->where('payment_status', '!=', 'pending')->doesntExist();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasMany<Training, $this>
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }

    /**
     * @return HasMany<ClubMatch, $this>
     */
    public function matches(): HasMany
    {
        return $this->hasMany(ClubMatch::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return BelongsToMany<Subscription, $this>
     */
    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Subscription::class, 'tenant_subscriptions')
            ->withPivot(['subscription_time', 'payment_type']);
    }
}
