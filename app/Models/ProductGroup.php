<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductGroup extends Model
{
    protected $fillable = ['site_id', 'name', 'slug', 'description'];

    protected static function booted(): void
    {
        static::saving(function (self $group) {
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name);
            }
        });
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }
}
