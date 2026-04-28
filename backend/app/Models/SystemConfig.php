<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_config';

    /**
     * Disable timestamps for this model
     */
    public $timestamps = false;

    /**
     * Configuration type constants
     */
    const TYPE_STRING = 'string';
    const TYPE_NUMBER = 'number';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';

    /**
     * Get configuration value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $config = self::where('key', $key)->first();
        
        if (!$config) {
            return $default;
        }

        return self::castValue($config->value, $config->type);
    }

    /**
     * Set configuration value
     */
    public static function setValue(string $key, $value, string $type = self::TYPE_STRING, string $description = null): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
                'description' => $description,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Cast value based on type
     */
    private static function castValue($value, string $type)
    {
        return match ($type) {
            self::TYPE_NUMBER => (float) $value,
            self::TYPE_BOOLEAN => (bool) $value,
            self::TYPE_JSON => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get all configuration as array
     */
    public static function getAllConfig(): array
    {
        $configs = self::all();
        $result = [];

        foreach ($configs as $config) {
            $result[$config->key] = self::castValue($config->value, $config->type);
        }

        return $result;
    }
}
