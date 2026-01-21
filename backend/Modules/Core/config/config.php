<?php

declare(strict_types=1);

return [
    'functional_name' => 'Core',
    'description' => 'Funciones transversales del área interna',
    'module_slug' => 'core',
    'auth_guard' => 'staff',
    'inertia_view_directory' => 'core',
    'base_permission' => null,

    'cache' => [
        'nav_cache_prefix' => 'core:nav:',
        'nav_version_key' => 'core.nav_version',
        'modules_statuses_mtime_key' => 'core.modules_statuses_mtime',
        'nav_assembled_ttl_seconds' => 300,
        'breadcrumbs_ttl_seconds' => 300,
        'global_nav_items_ttl_seconds' => 300,
    ],

    'nav_components' => [
        'links' => [
            'dashboard' => [
                'title' => 'Dashboard',
                'route_name' => 'internal.staff.dashboard',
                'icon' => 'LayoutDashboard',
                'permission' => null,
            ],
            'profile' => [
                'title' => 'Perfil',
                'route_name' => 'internal.staff.profile.edit',
                'icon' => 'UserCog',
                'permission' => null,
            ],
            'password' => [
                'title' => 'Contraseña',
                'route_name' => 'internal.staff.password.edit',
                'icon' => 'KeyRound',
                'permission' => null,
            ],
            'appearance' => [
                'title' => 'Apariencia',
                'route_name' => 'internal.staff.appearance',
                'icon' => 'Palette',
                'permission' => null,
            ],
            'account_security' => [
                'title' => 'Seguridad',
                'route_name' => 'internal.staff.security.edit',
                'icon' => 'Shield',
                'permission' => null,
            ],
            'notification_preferences' => [
                'title' => 'Notificaciones',
                'route_name' => 'internal.staff.notifications.edit',
                'icon' => 'Bell',
                'permission' => null,
            ],
        ],

        'groups' => [
            'user_profile_nav' => [
                '$ref:nav_components.links.profile',
                '$ref:nav_components.links.password',
                '$ref:nav_components.links.appearance',
                '$ref:nav_components.links.account_security',
                '$ref:nav_components.links.notification_preferences',
            ],
        ],
    ],

    'contextual_nav' => [
        'default' => [
            '$ref:groups.user_profile_nav',
        ],
    ],

    'breadcrumb_components' => [
        'user_profile_root' => [
            'title' => 'Configuración',
            'route_name' => 'internal.staff.profile.edit',
        ],
        'user_profile_profile' => [
            'title' => 'Perfil',
            'route_name' => 'internal.staff.profile.edit',
        ],
        'user_profile_password' => [
            'title' => 'Contraseña',
            'route_name' => 'internal.staff.password.edit',
        ],
        'user_profile_appearance' => [
            'title' => 'Apariencia',
            'route_name' => 'internal.staff.appearance',
        ],
        'user_profile_security' => [
            'title' => 'Seguridad',
            'route_name' => 'internal.staff.security.edit',
        ],
        'user_profile_notifications' => [
            'title' => 'Notificaciones',
            'route_name' => 'internal.staff.notifications.edit',
        ],
    ],
    'breadcrumbs' => [
        'profile.edit' => [
            '$ref:breadcrumb_components.user_profile_root',
            '$ref:breadcrumb_components.user_profile_profile',
        ],
        'password.edit' => [
            '$ref:breadcrumb_components.user_profile_root',
            '$ref:breadcrumb_components.user_profile_password',
        ],
        'appearance' => [
            '$ref:breadcrumb_components.user_profile_root',
            '$ref:breadcrumb_components.user_profile_appearance',
        ],
        'security.edit' => [
            '$ref:breadcrumb_components.user_profile_root',
            '$ref:breadcrumb_components.user_profile_security',
        ],
        'notifications.edit' => [
            '$ref:breadcrumb_components.user_profile_root',
            '$ref:breadcrumb_components.user_profile_notifications',
        ],
    ],
];
