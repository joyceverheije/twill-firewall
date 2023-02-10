<?php

namespace A17\TwillFirewall\Models;

use A17\Twill\Models\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use A17\Twill\Models\Behaviors\HasRevisions;
use A17\TwillFirewall\Services\Helpers;
use Illuminate\Database\Eloquent\Relations\HasMany;
use A17\TwillFirewall\Models\Behaviors\Encrypt;
use A17\TwillFirewall\Support\Facades\TwillFirewall as TwillFirewallFacade;

/**
 * @property string|null $domain
 */
class TwillFirewall extends Model
{
    use HasRevisions;
    use Encrypt;

    protected $table = 'twill_firewall';

    protected $fillable = ['published', 'domain', 'username', 'password', 'allow_laravel_login', 'allow_twill_login'];

    protected $appends = ['domain_string', 'status', 'from_dot_env'];

    public function getUsernameAttribute(): string|null
    {
        return $this->decrypt(
            Helpers::instance()
                ->setCurrent($this)
                ->username(true),
        );
    }

    public function setUsernameAttribute(string|null $value): void
    {
        $this->attributes['username'] = $this->encrypt($value);
    }

    public function getPasswordAttribute(): string|null
    {
        return $this->decrypt(
            Helpers::instance()
                ->setCurrent($this)
                ->password(true),
        );
    }

    public function setPasswordAttribute(string|null $value): void
    {
        $this->attributes['password'] = $this->encrypt($value);
    }

    public function getPublishedAttribute(): string|null
    {
        return Helpers::instance()
            ->setCurrent($this)
            ->published(true);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany($this->getRevisionModel(), 'twill_firewall_id')->orderBy('created_at', 'desc');
    }

    public function getDomainStringAttribute(): string|null
    {
        $domain = $this->domain;

        if ($domain === '*') {
            return '* (all domains)';
        }

        return $domain;
    }

    public function getConfiguredAttribute(): bool
    {
        return filled($this->username) && filled($this->password);
    }

    public function getStatusAttribute(): string
    {
        if ($this->published && $this->configured) {
            return 'protected';
        }

        if ($this->domain === '*') {
            return 'disabled';
        }

        return 'unprotected';
    }

    public function getFromDotEnvAttribute(): string
    {
        return TwillFirewallFacade::hasDotEnv() ? 'yes' : 'no';
    }
}
