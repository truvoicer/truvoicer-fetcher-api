<?php
namespace App\Enums\Entity;

use App\Models\Property;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Models\SrParameter;
use App\Models\User;

enum EntityType: string {
    case ENTITY_SR = 'service_requests';
    case ENTITY_PROVIDER = 'providers';
    case ENTITY_USER = 'users';
    case ENTITY_SERVICE = 'services';
    case ENTITY_PROPERTY = 'properties';
    case ENTITY_SR_CONFIG = 'sr_configs';
    case ENTITY_SR_PARAMETER = 'sr_parameters';

    public function className() {
        return match($this) {
            self::ENTITY_SR => Sr::class,
            self::ENTITY_PROVIDER => Provider::class,
            self::ENTITY_USER => User::class,
            self::ENTITY_SERVICE => S::class,
            self::ENTITY_PROPERTY => Property::class,
            self::ENTITY_SR_CONFIG => SrConfig::class,
            self::ENTITY_SR_PARAMETER => SrParameter::class,
        };
    }


    /**
     * Returns an EntityType case based on the fully qualified model class name.
     *
     * @param string $className The FQCN of the model (e.g., App\Models\User::class).
     * @return self
     * @throws \ValueError if the class name does not map to any EntityType case.
     */
    public static function fromClassName(string $className): self {
        foreach (self::cases() as $case) {
            if ($case->className() === $className) {
                return $case;
            }
        }

        // If no match is found, throw a ValueError, similar to Enum::from() behavior.
        throw new \ValueError(sprintf(
            "\"%s\" is not a valid class name for enum %s.",
            $className,
            self::class
        ));
    }
}
