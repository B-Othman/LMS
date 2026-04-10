<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    protected $table = 'notification_templates';

    protected $fillable = [
        'tenant_id',
        'type',
        'subject_template',
        'body_html_template',
        'body_text_template',
        'channel',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Render a template string by replacing {{placeholder}} tokens with data values.
     *
     * @param  array<string, string>  $data
     */
    public function renderSubject(array $data): string
    {
        return self::interpolate($this->subject_template, $data);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function renderBodyHtml(array $data): string
    {
        return self::interpolate($this->body_html_template, $data);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function renderBodyText(array $data): string
    {
        return self::interpolate($this->body_text_template, $data);
    }

    /**
     * @param  array<string, string>  $data
     */
    private static function interpolate(string $template, array $data): string
    {
        $search = array_map(fn ($key) => '{{'.$key.'}}', array_keys($data));
        $replace = array_values($data);

        return str_replace($search, $replace, $template);
    }
}
