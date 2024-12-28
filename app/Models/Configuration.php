<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $fillable = [
        'name',
        'numeric_value',
        'text_value',
        'json_value',
    ];

    protected $casts = [
        'numeric_value' => 'integer',
        'text_value' => 'string',
        'json_value' => 'array',
    ];

    protected $primaryKey = 'id';
    protected $table = 'config';
    public $timestamps = false;

    public function getValueAttribute()
    {

        if ($this->json_value !== null) {
            return ($this->json_value);
        }

        return $this->numeric_value ?? $this->text_value;
    }

    public function getPublicValueAttribute()
    {

        $value = $this->getValueAttribute();

        if ($this->is_private && $value) {
            return config('system.hidden_field');
        } else {
            return $value;
        }
    }

    public function scopeFilterByName($query, $name = null)
    {
        if (! $name) {
            return $query;
        }

        return $query->where('name', $name);
    }
}
