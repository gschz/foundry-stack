<?php

declare(strict_types=1);

return [
    'functional_name' => 'Core',
    'description' => 'Funciones transversales del área interna',
    'module_slug' => 'core',
    'auth_guard' => 'staff',
    'inertia_view_directory' => 'core',
    'base_permission' => null,
    'nav_components' => [
        'links' => [
            //
            'dashboard' => [
                'title' => 'Dashboard',
                'route_name' => 'internal.dashboard',
                'icon' => 'LayoutDashboard',
                'permission' => null,
            ],
            //
            'profile' => [
                'title' => 'Perfil',
                'route_name' => 'internal.user.settings.profile.edit',
                'icon' => 'UserCog',
                'permission' => null,
            ],
            'password' => [
                'title' => 'Contraseña',
                'route_name' => 'internal.user.settings.password.edit',
                'icon' => 'KeyRound',
                'permission' => null,
            ],
            'appearance' => [
                'title' => 'Apariencia',
                'route_name' => 'internal.user.settings.appearance',
                'icon' => 'Palette',
                'permission' => null,
            ],
        ],
        'groups' => [
            'user_settings_nav' => [
                '$ref:nav_components.links.profile',
                '$ref:nav_components.links.password',
                '$ref:nav_components.links.appearance',
            ],
        ],
    ],
    'contextual_nav' => [
        'default' => [
            '$ref:groups.user_settings_nav',
        ],
    ],
    'breadcrumb_components' => [
        'user_settings_root' => [
            'title' => 'Configuración',
            'route_name' => 'internal.user.settings.profile.edit',
        ],
        'user_settings_profile' => [
            'title' => 'Perfil',
            'route_name' => 'internal.user.settings.profile.edit',
        ],
        'user_settings_password' => [
            'title' => 'Contraseña',
            'route_name' => 'internal.user.settings.password.edit',
        ],
        'user_settings_appearance' => [
            'title' => 'Apariencia',
            'route_name' => 'internal.user.settings.appearance',
        ],
    ],
    'breadcrumbs' => [
        'user.settings.profile.edit' => [
            '$ref:breadcrumb_components.user_settings_root',
            '$ref:breadcrumb_components.user_settings_profile',
        ],
        'user.settings.password.edit' => [
            '$ref:breadcrumb_components.user_settings_root',
            '$ref:breadcrumb_components.user_settings_password',
        ],
        'user.settings.appearance' => [
            '$ref:breadcrumb_components.user_settings_root',
            '$ref:breadcrumb_components.user_settings_appearance',
        ],
    ],
];
