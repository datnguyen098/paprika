<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryImageTranslation extends Model
{
    protected $fillable = ['gallery_image_id', 'locale', 'title', 'slug', 'description', 'alt_text', 'meta_title', 'meta_description'];

    public function galleryImage(): BelongsTo
    {
        return $this->belongsTo(GalleryImage::class);
    }
}
