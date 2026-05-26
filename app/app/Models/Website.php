<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Website extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'demo_slug', 'url', 'domain', 'logo_url', 'brand_colors',
        'language', 'timezone', 'bot_token', 'verification_token', 'is_active', 'widget_enabled',
        'category', 'contact_email', 'bot_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'widget_enabled' => 'boolean',
            'brand_colors' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Website $website) {
            if (empty($website->bot_token)) {
                $website->bot_token = 'bot_'.Str::random(48);
            }
            if (empty($website->verification_token)) {
                $website->verification_token = 'vt_'.Str::random(32);
            }
        });

        static::created(function (Website $website) {
            $website->configuration()->create([]);
            foreach (range(0, 6) as $day) {
                $website->operatingHours()->create([
                    'day_of_week' => $day,
                    'opens_at' => '09:00:00',
                    'closes_at' => '17:00:00',
                    'timezone' => config('app.timezone', 'UTC'),
                ]);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function configuration(): HasOne
    {
        return $this->hasOne(ChatbotConfiguration::class);
    }

    public function suggestedQuestions(): HasMany
    {
        return $this->hasMany(SuggestedQuestion::class)->orderBy('sort_order');
    }

    public function operatingHours(): HasMany
    {
        return $this->hasMany(OperatingHour::class)->orderBy('day_of_week');
    }

    public function allowedDomains(): HasMany
    {
        return $this->hasMany(AllowedDomain::class);
    }

    public function knowledgeCategories(): HasMany
    {
        return $this->hasMany(KnowledgeCategory::class);
    }

    public function knowledgeArticles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class);
    }

    public function knowledgeTags(): HasMany
    {
        return $this->hasMany(KnowledgeTag::class);
    }

    public function knowledgeSynonyms(): HasMany
    {
        return $this->hasMany(KnowledgeSynonym::class);
    }

    public function qaPairs(): HasMany
    {
        return $this->hasMany(QaPair::class);
    }

    public function triggerKeywords(): HasMany
    {
        return $this->hasMany(TriggerKeyword::class);
    }

    public function escalationRules(): HasMany
    {
        return $this->hasMany(EscalationRule::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    public function quickActions(): HasMany
    {
        return $this->hasMany(QuickAction::class)->orderBy('sort_order');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function unansweredQuestions(): HasMany
    {
        return $this->hasMany(UnansweredQuestion::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function knowledgeSources(): HasMany
    {
        return $this->hasMany(KnowledgeSource::class);
    }

    public function embedSnippet(): string
    {
        $base = rtrim(config('app.url'), '/');

        return <<<HTML
<script>
(function(w,d,s,o,f,js,fjs){
  w['AIChatbotWidget']=o;
  w[o]=w[o]||function(){(w[o].q=w[o].q||[]).push(arguments)};
  js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
  js.src=f; js.async=1;
  fjs.parentNode.insertBefore(js,fjs);
})(window,document,'script','aichatbot','{$base}/widget/loader.js');
aichatbot('init',{ website_key:'{$this->bot_token}' });
</script>
HTML;
    }

    public function simpleEmbedSnippet(): string
    {
        $base = rtrim(config('app.url'), '/');

        return sprintf(
            '<script src="%s/widget/chatbot.js" data-bot-token="%s" async></script>',
            $base,
            e($this->bot_token)
        );
    }
}
