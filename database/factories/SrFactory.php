<?php

namespace Database\Factories;

use Truvoicer\TfDbReadCore\Enums\Api\ApiListKey;
use Truvoicer\TfDbReadCore\Enums\FormatOptions;
use App\Enums\Sr\PaginationType;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sr>
 */
class SrFactory extends Factory
{
    private array $availablePregMatches = [

        // Email validation (RFC 5322 compliant)
        'email' => '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/',

        // Simple email (less strict)
        'email_simple' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',

        // URL validation
        'url' => '/^(https?|ftp):\/\/([a-zA-Z0-9.-]+(:[a-zA-Z0-9.&%$-]+)*@)*((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}|([a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]+\.(com|edu|gov|int|mil|net|org|biz|info|name|pro|[a-z]{2}))(:[0-9]+)*(\/($|[a-zA-Z0-9.,?\'\\+&%$#=~_-]+))*$/',

        // Simple URL
        'url_simple' => '/^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([\/\w .-]*)*\/?$/',

        // IP address (IPv4)
        'ipv4' => '/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/',

        // IP address (IPv6)
        'ipv6' => '/^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$/',

        // Username (alphanumeric, underscores, hyphens, 3-20 chars)
        'username' => '/^[a-zA-Z0-9_-]{3,20}$/',

        // Strong password (at least 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special)
        'password_strong' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',

        // Date (YYYY-MM-DD)
        'date_ymd' => '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/',

        // Date (DD/MM/YYYY)
        'date_dmy' => '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/',

        // Time (24-hour format)
        'time_24h' => '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',

        // Time (12-hour format)
        'time_12h' => '/^(0?[1-9]|1[0-2]):[0-5][0-9]\s?(AM|PM|am|pm)$/',

        // Phone number (US format)
        'phone_us' => '/^(\+?1\s?)?(\([0-9]{3}\)|[0-9]{3})[\s.-]?[0-9]{3}[\s.-]?[0-9]{4}$/',

        // Phone number (international, simplified)
        'phone_international' => '/^\+(?:[0-9] ?){6,14}[0-9]$/',

        // Credit card (Visa, MasterCard, American Express, Discover)
        'credit_card' => '/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})$/',

        // ZIP code (US)
        'zip_us' => '/^\d{5}(-\d{4})?$/',

        // Postal code (Canada)
        'postal_code_ca' => '/^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/',

        // Hexadecimal color code
        'hex_color' => '/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/',

        // RGB color
        'rgb_color' => '/^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/',

        // RGBA color
        'rgba_color' => '/^rgba\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3}),\s*(0|1|0?\.\d+)\)$/',

        // Slug (for URLs)
        'slug' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',

        // Alphanumeric only
        'alphanumeric' => '/^[a-zA-Z0-9]+$/',

        // Letters only (no numbers)
        'letters_only' => '/^[a-zA-Z\s]+$/',

        // Numbers only
        'numbers_only' => '/^\d+$/',

        // Positive integers only
        'positive_integer' => '/^[1-9]\d*$/',

        // Integer (positive or negative)
        'integer' => '/^-?\d+$/',

        // Decimal number
        'decimal' => '/^-?\d+(\.\d+)?$/',

        // Money (USD format)
        'money_usd' => '/^\$?(\d{1,3}(\,\d{3})*|(\d+))(\.\d{2})?$/',

        // Social Security Number (US)
        'ssn' => '/^\d{3}-\d{2}-\d{4}$/',

        // UUID v4
        'uuid_v4' => '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',

        // MAC address
        'mac_address' => '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',

        // HTML tag detection
        'html_tag' => '/<([a-z]+)([^<]+)*(?:>(.*)<\/\1>|\s+\/>)/',

        // Base64 string
        'base64' => '/^[a-zA-Z0-9\/\r\n+]*={0,2}$/',

        // JWT token (simplified)
        'jwt' => '/^[A-Za-z0-9-_=]+\.[A-Za-z0-9-_=]+\.?[A-Za-z0-9-_.+/=]*$/',

        // File extension (common image formats)
        'image_file' => '/\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i',

        // File extension (common document formats)
        'document_file' => '/\.(pdf|doc|docx|txt|rtf|odt)$/i',

        // YouTube video ID
        'youtube_id' => '/^[a-zA-Z0-9_-]{11}$/',

        // Twitter handle
        'twitter_handle' => '/^@[A-Za-z0-9_]{1,15}$/',

        // Instagram handle
        'instagram_handle' => '/^@[A-Za-z0-9._]{1,30}$/',

        // Hashtag
        'hashtag' => '/^#[A-Za-z0-9_]+$/',

        // Domain name (without protocol)
        'domain' => '/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',

        // MongoDB ObjectId
        'mongodb_id' => '/^[0-9a-fA-F]{24}$/',

        // ISO 8601 date/time
        'iso8601' => '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/',

        // Latitude (-90 to 90)
        'latitude' => '/^-?([1-8]?[0-9]\.\d+|90\.0+)$/',

        // Longitude (-180 to 180)
        'longitude' => '/^-?((1[0-7][0-9]|[1-9]?[0-9])\.\d+|180\.0+)$/',

        // Vehicle VIN (simplified)
        'vin' => '/^[A-HJ-NPR-Z0-9]{17}$/',

        // ISBN (10 or 13 digits)
        'isbn' => '/^(?:ISBN(?:-1[03])?:?\s)?(?=[0-9X]{10}$|(?=(?:[0-9]+[-\s]){3})[-\s0-9X]{13}$|97[89][0-9]{10}$|(?=(?:[0-9]+[-\s]){4})[-\s0-9]{17}$)(?:97[89][-\s]?)?[0-9]{1,5}[-\s]?[0-9]+[-\s]?[0-9]+[-\s]?[0-9X]$/',

        // CSS class selector
        'css_class' => '/^-?[_a-zA-Z]+[_a-zA-Z0-9-]*$/',
    ];
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = $this->faker->word;
        return [
            ApiListKey::LIST_KEY->value => $this->faker->word,
            ApiListKey::LIST_ITEM_REPEATER_KEY->value => $this->faker->word,
            'name' => Str::slug($label),
            'label' => $label,
            'default_sr' => $this->faker->boolean,
            'type' => $this->faker->randomElement(SrType::values()),
            'pagination_type' => $this->faker->randomElement(PaginationType::values()),
            ApiListKey::LIST_FORMAT_OPTIONS->value => $this->faker->randomElement(FormatOptions::values()),
            ApiListKey::LIST_FORMAT_OPTION_PREG_MATCH->value => $this->faker->randomElement(
                array_values($this->availablePregMatches)
            ),
            'query_parameters' => array_combine($this->faker->words(3), $this->faker->words(3)),
            'default_data' => array_combine($this->faker->words(3), $this->faker->words(3)),
        ];
    }
}
