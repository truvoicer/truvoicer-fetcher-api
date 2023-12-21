<?php

namespace App\Services\Auth;

class AuthService
{
    const ABILITY_SUPERUSER = 'superuser';
    const ABILITY_SUPER_ADMIN = 'super_admin';
    const ABILITY_PUBLIC = 'public';
    const ABILITY_ADMIN = 'admin';
    const ABILITY_APP_USER = 'app_user';
    const ABILITY_USER = 'user';
    const DEFAULT_ROLES = [
        [
            'name' => self::ABILITY_SUPERUSER,
            'label' => 'Super User',
            'ability' => 'api:superuser'
        ],
        [
            'name' => self::ABILITY_SUPER_ADMIN,
            'label' => 'Super Admin',
            'ability' => 'api:super_admin'
        ],
        [
            'name' => self::ABILITY_ADMIN,
            'label' => 'Admin',
            'ability' => 'api:admin'
        ],
        [
            'name' => self::ABILITY_USER,
            'label' => 'User',
            'ability' => 'api:user'
        ],
        [
            'name' => self::ABILITY_APP_USER,
            'label' => 'App User',
            'ability' => 'api:app_user'
        ],
        [
            'name' => self::ABILITY_PUBLIC,
            'label' => 'Public',
            'ability' => 'api:public'
        ]
    ];

    public static function getApiAbility(string $name)
    {
        $findAbilityIndex = array_search($name, array_column(self::DEFAULT_ROLES, 'name'));
        if ($findAbilityIndex === false) {
            return false;
        }
        return self::DEFAULT_ROLES[$findAbilityIndex]['ability'];
    }

    public static function getApiAbilityData(string $name)
    {
        $findAbilityIndex = array_search($name, array_column(self::DEFAULT_ROLES, 'name'));
        if ($findAbilityIndex === false) {
            return false;
        }
        return self::DEFAULT_ROLES[$findAbilityIndex];
    }
}
