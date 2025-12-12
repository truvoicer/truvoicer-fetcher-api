<?php

namespace App\Models\Mongo;

use Database\Factories\Mongo\EntityListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class EntityList extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $table = 'entity_list';

    protected $fillable = [
        'status',
        'requestType',
        'responseFormat',
        'contentType',
        'provider',
        'serviceRequest',
        'service',
        'requestCategory',
        'extraData',
        'paginationType',
        'item_id',
        'date_expires',
        'description',
        'title',
        'location_name',
        'date_added',
        'external_url',
        'query_params',
        'slug',
        'is_active',
        'excerpt',
        'keywords',
        'location',
        'website',
        'contact_email',
        'contact_phone',
        'is_featured',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public $timestamps = true;

    static public function newFactory()
    {
        return EntityListFactory::new();
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
    public function setProvider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }
    public function getService(): array
    {
        return $this->service;
    }
    public function setService(string $service): self
    {
        $this->service = $service;
        return $this;
    }
    public function getItemId(): string
    {
        return $this->item_id;
    }
    public function setItemId(string $itemId): self
    {
        $this->item_id = $itemId;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getIsActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->is_active = $isActive;
    }

    public function getCompanyId(): ?int
    {
        return $this->company_id;
    }

    public function setCompanyId(?int $companyId): void
    {
        $this->company_id = $companyId;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): void
    {
        $this->excerpt = $excerpt;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    public function getLocationName(): ?string
    {
        return $this->location_name;
    }

    public function setLocationName(?string $locationName): void
    {
        $this->location_name = $locationName;
    }

    public function getDateExpires(): ?\Carbon\Carbon
    {
        return $this->date_expires;
    }

    public function setDateExpires(?\Carbon\Carbon $dateExpires): void
    {
        $this->date_expires = $dateExpires;
    }

    public function getDateAdded(): ?\Carbon\Carbon
    {
        return $this->date_added;
    }

    public function setDateAdded(?\Carbon\Carbon $dateAdded): void
    {
        $this->date_added = $dateAdded;
    }

    public function getExternalUrl(): ?string
    {
        return $this->external_url;
    }

    public function setExternalUrl(?string $externalUrl): void
    {
        $this->external_url = $externalUrl;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public function getContactEmail(): ?string
    {
        return $this->contact_email;
    }

    public function setContactEmail(?string $contactEmail): void
    {
        $this->contact_email = $contactEmail;
    }

    public function getContactPhone(): ?string
    {
        return $this->contact_phone;
    }

    public function setContactPhone(?string $contactPhone): void
    {
        $this->contact_phone = $contactPhone;
    }

    public function getIsFeatured(): bool
    {
        return $this->is_featured;
    }

    public function setIsFeatured(bool $isFeatured): void
    {
        $this->is_featured = $isFeatured;
    }
}
