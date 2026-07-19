<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Newsletter extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subject',
        'body',
        'image_url',
        'status',
        'sent_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Absolute file path when image_url points at our own public storage,
     * null for external URLs. Local uploads must be embedded into the email
     * (mail clients can't fetch a dev machine's localhost URLs).
     */
    public function localImagePath(): ?string
    {
        if (! $this->image_url) {
            return null;
        }

        $urlPath = parse_url($this->image_url, PHP_URL_PATH);

        if (! $urlPath || ! str_starts_with($urlPath, '/storage/')) {
            return null;
        }

        $file = Storage::disk('public')->path(substr($urlPath, strlen('/storage/')));

        return is_file($file) ? $file : null;
    }
}
