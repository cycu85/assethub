<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/_profiler' => [[['_route' => '_profiler_home', '_controller' => 'web_profiler.controller.profiler::homeAction'], null, null, null, true, false, null]],
        '/_profiler/search' => [[['_route' => '_profiler_search', '_controller' => 'web_profiler.controller.profiler::searchAction'], null, null, null, false, false, null]],
        '/_profiler/search_bar' => [[['_route' => '_profiler_search_bar', '_controller' => 'web_profiler.controller.profiler::searchBarAction'], null, null, null, false, false, null]],
        '/_profiler/phpinfo' => [[['_route' => '_profiler_phpinfo', '_controller' => 'web_profiler.controller.profiler::phpinfoAction'], null, null, null, false, false, null]],
        '/_profiler/xdebug' => [[['_route' => '_profiler_xdebug', '_controller' => 'web_profiler.controller.profiler::xdebugAction'], null, null, null, false, false, null]],
        '/_profiler/open' => [[['_route' => '_profiler_open_file', '_controller' => 'web_profiler.controller.profiler::openAction'], null, null, null, false, false, null]],
        '/admin' => [[['_route' => 'admin_dashboard', '_controller' => 'App\\Controller\\Admin\\AdminController::dashboard'], null, null, null, true, false, null]],
        '/admin/modules' => [[['_route' => 'admin_modules', '_controller' => 'App\\Controller\\Admin\\AdminController::modules'], null, null, null, false, false, null]],
        '/admin/settings' => [[['_route' => 'admin_settings', '_controller' => 'App\\Controller\\Admin\\AdminController::settings'], null, null, null, false, false, null]],
        '/admin/settings/general' => [[['_route' => 'admin_settings_general', '_controller' => 'App\\Controller\\Admin\\AdminController::generalSettings'], null, null, null, false, false, null]],
        '/admin/settings/general/reset' => [[['_route' => 'admin_settings_general_reset', '_controller' => 'App\\Controller\\Admin\\AdminController::resetGeneralSettings'], null, ['POST' => 0], null, false, false, null]],
        '/admin/settings/email' => [[['_route' => 'admin_settings_email', '_controller' => 'App\\Controller\\Admin\\AdminController::emailSettings'], null, null, null, false, false, null]],
        '/admin/settings/database' => [[['_route' => 'admin_settings_database', '_controller' => 'App\\Controller\\Admin\\AdminController::databaseSettings'], null, null, null, false, false, null]],
        '/admin/settings/ldap' => [[['_route' => 'admin_settings_ldap', '_controller' => 'App\\Controller\\Admin\\AdminController::ldapSettings'], null, null, null, false, false, null]],
        '/admin/dictionaries' => [[['_route' => 'admin_dictionaries', '_controller' => 'App\\Controller\\Admin\\DictionaryController::index'], null, null, null, true, false, null]],
        '/admin/emails' => [[['_route' => 'admin_emails_index', '_controller' => 'App\\Controller\\Admin\\EmailController::index'], null, null, null, true, false, null]],
        '/admin/emails/stats' => [[['_route' => 'admin_emails_stats', '_controller' => 'App\\Controller\\Admin\\EmailController::stats'], null, null, null, false, false, null]],
        '/admin/emails/cleanup' => [[['_route' => 'admin_emails_cleanup', '_controller' => 'App\\Controller\\Admin\\EmailController::cleanup'], null, ['POST' => 0], null, false, false, null]],
        '/admin/equipment-categories' => [[['_route' => 'admin_equipment_categories_index', '_controller' => 'App\\Controller\\Admin\\EquipmentCategoryController::index'], null, null, null, true, false, null]],
        '/admin/equipment-categories/new' => [[['_route' => 'admin_equipment_categories_new', '_controller' => 'App\\Controller\\Admin\\EquipmentCategoryController::new'], null, null, null, false, false, null]],
        '/admin/logs' => [[['_route' => 'admin_logs', '_controller' => 'App\\Controller\\Admin\\LogController::index'], null, null, null, true, false, null]],
        '/admin/roles' => [[['_route' => 'admin_roles_index', '_controller' => 'App\\Controller\\Admin\\RoleController::index'], null, null, null, true, false, null]],
        '/admin/roles/new' => [[['_route' => 'admin_roles_new', '_controller' => 'App\\Controller\\Admin\\RoleController::new'], null, null, null, false, false, null]],
        '/admin/users' => [[['_route' => 'admin_users_index', '_controller' => 'App\\Controller\\Admin\\UserController::index'], null, null, null, true, false, null]],
        '/admin/users/new' => [[['_route' => 'admin_users_new', '_controller' => 'App\\Controller\\Admin\\UserController::new'], null, null, null, false, false, null]],
        '/' => [
            [['_route' => 'dashboard', '_controller' => 'App\\Controller\\DashboardController::index'], null, null, null, false, false, null],
            [['_route' => 'home', '_controller' => 'App\\Controller\\HomeController::index'], null, null, null, false, false, null],
        ],
        '/assets/css/dynamic-theme.css' => [[['_route' => 'dynamic_css', '_controller' => 'App\\Controller\\DynamicCssController::generateCss'], null, null, null, false, false, null]],
        '/error/access-denied' => [[['_route' => 'error_access_denied', '_controller' => 'App\\Controller\\ErrorController::accessDenied'], null, null, null, false, false, null]],
        '/error/not-found' => [[['_route' => 'error_not_found', '_controller' => 'App\\Controller\\ErrorController::notFound'], null, null, null, false, false, null]],
        '/install' => [[['_route' => 'installer_welcome', '_controller' => 'App\\Controller\\InstallerController::welcome'], null, null, null, true, false, null]],
        '/install/requirements' => [[['_route' => 'installer_requirements', '_controller' => 'App\\Controller\\InstallerController::requirements'], null, null, null, false, false, null]],
        '/install/database' => [[['_route' => 'installer_database', '_controller' => 'App\\Controller\\InstallerController::database'], null, null, null, false, false, null]],
        '/install/admin' => [[['_route' => 'installer_admin', '_controller' => 'App\\Controller\\InstallerController::admin'], null, null, null, false, false, null]],
        '/install/finish' => [[['_route' => 'installer_finish', '_controller' => 'App\\Controller\\InstallerController::finish'], null, null, null, false, false, null]],
        '/api/notifications' => [[['_route' => 'api_notifications_list', '_controller' => 'App\\Controller\\NotificationController::list'], null, ['GET' => 0], null, false, false, null]],
        '/api/notifications/count' => [[['_route' => 'api_notifications_count', '_controller' => 'App\\Controller\\NotificationController::count'], null, ['GET' => 0], null, false, false, null]],
        '/api/notifications/stats' => [[['_route' => 'api_notifications_stats', '_controller' => 'App\\Controller\\NotificationController::stats'], null, ['GET' => 0], null, false, false, null]],
        '/api/notifications/mark-all-read' => [[['_route' => 'api_notifications_mark_all_read', '_controller' => 'App\\Controller\\NotificationController::markAllAsRead'], null, ['POST' => 0], null, false, false, null]],
        '/api/notifications/mark-multiple-read' => [[['_route' => 'api_notifications_mark_multiple_read', '_controller' => 'App\\Controller\\NotificationController::markMultipleAsRead'], null, ['POST' => 0], null, false, false, null]],
        '/profile' => [[['_route' => 'profile', '_controller' => 'App\\Controller\\ProfileController::index'], null, null, null, false, false, null]],
        '/api/search' => [[['_route' => 'api_search', '_controller' => 'App\\Controller\\SearchController::search'], null, ['GET' => 0], null, false, false, null]],
        '/login' => [[['_route' => 'login', '_controller' => 'App\\Controller\\SecurityController::login'], null, null, null, false, false, null]],
        '/logout' => [[['_route' => 'logout', '_controller' => 'App\\Controller\\SecurityController::logout'], null, null, null, false, false, null]],
        '/asekuracja' => [[['_route' => 'asekuracja_index', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::index'], null, null, null, true, false, null]],
        '/asekuracja/equipment/new' => [[['_route' => 'asekuracja_equipment_new', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::newEquipment'], null, null, null, false, false, null]],
        '/asekuracja/search' => [[['_route' => 'asekuracja_search', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::search'], null, null, null, false, false, null]],
        '/asekuracja/my-equipment' => [[['_route' => 'asekuracja_my_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::myEquipment'], null, null, null, false, false, null]],
        '/asekuracja/equipment-sets' => [[['_route' => 'asekuracja_equipment_set_index', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::index'], null, null, null, true, false, null]],
        '/asekuracja/equipment-sets/new' => [[['_route' => 'asekuracja_equipment_set_new', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::new'], null, null, null, false, false, null]],
        '/asekuracja/equipment-sets/available-equipment' => [[['_route' => 'asekuracja_available_equipment_modal', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::availableEquipmentModal'], null, null, null, false, false, null]],
        '/asekuracja/reviews' => [[['_route' => 'asekuracja_review_index', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::index'], null, null, null, true, false, null]],
        '/asekuracja/reviews/new' => [[['_route' => 'asekuracja_review_new', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::new'], null, null, null, false, false, null]],
        '/aparatura-pomiarowa/equipment/new' => [[['_route' => 'aparatura_pomiarowa_equipment_new', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::newEquipment'], null, null, null, false, false, null]],
        '/aparatura-pomiarowa/search' => [[['_route' => 'aparatura_pomiarowa_search', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::search'], null, null, null, false, false, null]],
        '/aparatura-pomiarowa/my-equipment' => [[['_route' => 'aparatura_pomiarowa_my_equipment', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::myEquipment'], null, null, null, false, false, null]],
        '/aparatura-pomiarowa/equipment-sets' => [[['_route' => 'aparatura_pomiarowa_equipment_set_index', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::index'], null, null, null, true, false, null]],
        '/aparatura-pomiarowa/equipment-sets/new' => [[['_route' => 'aparatura_pomiarowa_equipment_set_new', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::new'], null, null, null, false, false, null]],
        '/aparatura-pomiarowa/equipment-sets/available-equipment' => [[['_route' => 'aparatura_pomiarowa_available_equipment_modal', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::availableEquipmentModal'], null, null, null, false, false, null]],
        '/aparatura-pomiarowa/reviews' => [[['_route' => 'aparatura_pomiarowa_review_index', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::index'], null, null, null, true, false, null]],
        '/aparatura-pomiarowa/reviews/new' => [[['_route' => 'aparatura_pomiarowa_review_new', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::new'], null, null, null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/_(?'
                    .'|error/(\\d+)(?:\\.([^/]++))?(*:38)'
                    .'|wdt/([^/]++)(*:57)'
                    .'|profiler/(?'
                        .'|font/([^/\\.]++)\\.woff2(*:98)'
                        .'|([^/]++)(?'
                            .'|/(?'
                                .'|search/results(*:134)'
                                .'|router(*:148)'
                                .'|exception(?'
                                    .'|(*:168)'
                                    .'|\\.css(*:181)'
                                .')'
                            .')'
                            .'|(*:191)'
                        .')'
                    .')'
                .')'
                .'|/admin/(?'
                    .'|dictionaries/(?'
                        .'|type/([^/]++)(*:241)'
                        .'|new/([^/]++)(*:261)'
                        .'|([^/]++)/(?'
                            .'|edit(*:285)'
                            .'|delete(*:299)'
                            .'|toggle\\-status(*:321)'
                        .')'
                        .'|api/type/([^/]++)(*:347)'
                    .')'
                    .'|e(?'
                        .'|mails/(\\d+)(*:371)'
                        .'|quipment\\-categories/(?'
                            .'|(\\d+)(*:408)'
                            .'|(\\d+)/edit(*:426)'
                            .'|(\\d+)/delete(*:446)'
                            .'|(\\d+)/toggle\\-status(*:474)'
                        .')'
                    .')'
                    .'|logs/(?'
                        .'|view/([^/]++)(*:505)'
                        .'|download/([^/]++)(*:530)'
                        .'|clear/([^/]++)(*:552)'
                    .')'
                    .'|roles/(?'
                        .'|(\\d+)(*:575)'
                        .'|(\\d+)/edit(*:593)'
                        .'|(\\d+)/delete(*:613)'
                    .')'
                    .'|users/(?'
                        .'|(\\d+)/roles(*:642)'
                        .'|(\\d+)(*:655)'
                        .'|(\\d+)/edit(*:673)'
                        .'|(\\d+)/toggle\\-status(*:701)'
                        .'|([^/]++)/ldap/(?'
                            .'|unlock(*:732)'
                            .'|re(?'
                                .'|set\\-password(*:758)'
                                .'|fresh(*:771)'
                            .')'
                        .')'
                    .')'
                .')'
                .'|/((?!install|admin|api|login|logout|profile|asekuracja|aparatura-pomiarowa/).*)(*:862)'
                .'|/a(?'
                    .'|p(?'
                        .'|i/notifications/(?'
                            .'|([^/]++)(?'
                                .'|/(?'
                                    .'|read(*:917)'
                                    .'|unread(*:931)'
                                .')'
                                .'|(*:940)'
                            .')'
                            .'|delete\\-multiple(*:965)'
                            .'|grouped(*:980)'
                        .')'
                        .'|aratura\\-pomiarowa(?'
                            .'|(*:1010)'
                            .'|/(?'
                                .'|equipment(?'
                                    .'|/(?'
                                        .'|(\\d+)(*:1044)'
                                        .'|(\\d+)/edit(*:1063)'
                                        .'|(\\d+)/delete(*:1084)'
                                        .'|(\\d+)/assign(*:1105)'
                                        .'|(\\d+)/unassign(*:1128)'
                                        .'|(\\d+)/attachment/upload(*:1160)'
                                        .'|(\\d+)/attachment/([^/]++)/download(*:1203)'
                                        .'|(\\d+)/attachment/([^/]++)/delete(*:1244)'
                                    .')'
                                    .'|\\-sets/(?'
                                        .'|(\\d+)(*:1269)'
                                        .'|(\\d+)/edit(*:1288)'
                                        .'|(\\d+)/delete(*:1309)'
                                        .'|(\\d+)/equipment/add(*:1337)'
                                        .'|(\\d+)/equipment/(\\d+)/remove(*:1374)'
                                        .'|(\\d+)/equipment/remove\\-bulk(*:1411)'
                                        .'|(\\d+)/attachment/upload(*:1443)'
                                        .'|(\\d+)/attachment/([^/]++)/download(*:1486)'
                                        .'|(\\d+)/attachment/([^/]++)/delete(*:1527)'
                                        .'|transfer/(?'
                                            .'|(\\d+)/prepare(*:1561)'
                                            .'|(\\d+)/complete(*:1584)'
                                            .'|(\\d+)/protocol/download(*:1616)'
                                            .'|(\\d+)/return(*:1637)'
                                        .')'
                                        .'|return/(?'
                                            .'|(\\d+)/prepare(*:1670)'
                                            .'|(\\d+)/complete(*:1693)'
                                            .'|(\\d+)/protocol/download(*:1725)'
                                        .')'
                                    .')'
                                .')'
                                .'|reviews/(?'
                                    .'|(\\d+)(*:1753)'
                                    .'|(\\d+)/edit(*:1772)'
                                    .'|new/equipment(?'
                                        .'|/(\\d+)(*:1803)'
                                        .'|\\-set/(\\d+)(*:1823)'
                                    .')'
                                    .'|(\\d+)/send(*:1843)'
                                    .'|(\\d+)/delete(*:1864)'
                                    .'|(\\d+)/complete(*:1887)'
                                    .'|(\\d+)/attachment/([^/]++)(*:1921)'
                                    .'|(\\d+)/equipment/add(*:1949)'
                                    .'|(\\d+)/equipment/(\\d+)/remove(*:1986)'
                                .')'
                            .')'
                        .')'
                    .')'
                    .'|sekuracja/(?'
                        .'|equipment(?'
                            .'|/(?'
                                .'|(\\d+)(*:2033)'
                                .'|(\\d+)/edit(*:2052)'
                                .'|(\\d+)/delete(*:2073)'
                                .'|(\\d+)/assign(*:2094)'
                                .'|(\\d+)/unassign(*:2117)'
                                .'|(\\d+)/attachment/upload(*:2149)'
                                .'|(\\d+)/attachment/([^/]++)/download(*:2192)'
                                .'|(\\d+)/attachment/([^/]++)/delete(*:2233)'
                            .')'
                            .'|\\-sets/(?'
                                .'|(\\d+)(*:2258)'
                                .'|(\\d+)/edit(*:2277)'
                                .'|(\\d+)/delete(*:2298)'
                                .'|(\\d+)/equipment/add(*:2326)'
                                .'|(\\d+)/equipment/(\\d+)/remove(*:2363)'
                                .'|(\\d+)/equipment/remove\\-bulk(*:2400)'
                                .'|(\\d+)/attachment/upload(*:2432)'
                                .'|(\\d+)/attachment/([^/]++)/download(*:2475)'
                                .'|(\\d+)/attachment/([^/]++)/delete(*:2516)'
                                .'|transfer/(?'
                                    .'|(\\d+)/prepare(*:2550)'
                                    .'|(\\d+)/complete(*:2573)'
                                    .'|(\\d+)/protocol/download(*:2605)'
                                    .'|(\\d+)/return(*:2626)'
                                .')'
                                .'|return/(?'
                                    .'|(\\d+)/prepare(*:2659)'
                                    .'|(\\d+)/complete(*:2682)'
                                    .'|(\\d+)/protocol/download(*:2714)'
                                .')'
                            .')'
                        .')'
                        .'|reviews/(?'
                            .'|(\\d+)(*:2742)'
                            .'|(\\d+)/edit(*:2761)'
                            .'|new/equipment(?'
                                .'|/(\\d+)(*:2792)'
                                .'|\\-set/(\\d+)(*:2812)'
                            .')'
                            .'|(\\d+)/send(*:2832)'
                            .'|(\\d+)/delete(*:2853)'
                            .'|(\\d+)/complete(*:2876)'
                            .'|(\\d+)/attachment/([^/]++)(*:2910)'
                            .'|(\\d+)/equipment/add(*:2938)'
                            .'|(\\d+)/equipment/(\\d+)/remove(*:2975)'
                        .')'
                    .')'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        38 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        57 => [[['_route' => '_wdt', '_controller' => 'web_profiler.controller.profiler::toolbarAction'], ['token'], null, null, false, true, null]],
        98 => [[['_route' => '_profiler_font', '_controller' => 'web_profiler.controller.profiler::fontAction'], ['fontName'], null, null, false, false, null]],
        134 => [[['_route' => '_profiler_search_results', '_controller' => 'web_profiler.controller.profiler::searchResultsAction'], ['token'], null, null, false, false, null]],
        148 => [[['_route' => '_profiler_router', '_controller' => 'web_profiler.controller.router::panelAction'], ['token'], null, null, false, false, null]],
        168 => [[['_route' => '_profiler_exception', '_controller' => 'web_profiler.controller.exception_panel::body'], ['token'], null, null, false, false, null]],
        181 => [[['_route' => '_profiler_exception_css', '_controller' => 'web_profiler.controller.exception_panel::stylesheet'], ['token'], null, null, false, false, null]],
        191 => [[['_route' => '_profiler', '_controller' => 'web_profiler.controller.profiler::panelAction'], ['token'], null, null, false, true, null]],
        241 => [[['_route' => 'admin_dictionaries_type', '_controller' => 'App\\Controller\\Admin\\DictionaryController::viewType'], ['type'], null, null, false, true, null]],
        261 => [[['_route' => 'admin_dictionaries_new', '_controller' => 'App\\Controller\\Admin\\DictionaryController::new'], ['type'], null, null, false, true, null]],
        285 => [[['_route' => 'admin_dictionaries_edit', '_controller' => 'App\\Controller\\Admin\\DictionaryController::edit'], ['id'], null, null, false, false, null]],
        299 => [[['_route' => 'admin_dictionaries_delete', '_controller' => 'App\\Controller\\Admin\\DictionaryController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        321 => [[['_route' => 'admin_dictionaries_toggle_status', '_controller' => 'App\\Controller\\Admin\\DictionaryController::toggleStatus'], ['id'], ['POST' => 0], null, false, false, null]],
        347 => [[['_route' => 'api_dictionaries_by_type', '_controller' => 'App\\Controller\\Admin\\DictionaryController::apiGetByType'], ['type'], ['GET' => 0], null, false, true, null]],
        371 => [[['_route' => 'admin_emails_show', '_controller' => 'App\\Controller\\Admin\\EmailController::show'], ['id'], null, null, false, true, null]],
        408 => [[['_route' => 'admin_equipment_categories_show', '_controller' => 'App\\Controller\\Admin\\EquipmentCategoryController::show'], ['id'], null, null, false, true, null]],
        426 => [[['_route' => 'admin_equipment_categories_edit', '_controller' => 'App\\Controller\\Admin\\EquipmentCategoryController::edit'], ['id'], null, null, false, false, null]],
        446 => [[['_route' => 'admin_equipment_categories_delete', '_controller' => 'App\\Controller\\Admin\\EquipmentCategoryController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        474 => [[['_route' => 'admin_equipment_categories_toggle_status', '_controller' => 'App\\Controller\\Admin\\EquipmentCategoryController::toggleStatus'], ['id'], ['POST' => 0], null, false, false, null]],
        505 => [[['_route' => 'admin_logs_view', '_controller' => 'App\\Controller\\Admin\\LogController::view'], ['filename'], null, null, false, true, null]],
        530 => [[['_route' => 'admin_logs_download', '_controller' => 'App\\Controller\\Admin\\LogController::download'], ['filename'], null, null, false, true, null]],
        552 => [[['_route' => 'admin_logs_clear', '_controller' => 'App\\Controller\\Admin\\LogController::clear'], ['filename'], ['POST' => 0], null, false, true, null]],
        575 => [[['_route' => 'admin_roles_show', '_controller' => 'App\\Controller\\Admin\\RoleController::show'], ['id'], null, null, false, true, null]],
        593 => [[['_route' => 'admin_roles_edit', '_controller' => 'App\\Controller\\Admin\\RoleController::edit'], ['id'], null, null, false, false, null]],
        613 => [[['_route' => 'admin_roles_delete', '_controller' => 'App\\Controller\\Admin\\RoleController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        642 => [[['_route' => 'admin_users_roles', '_controller' => 'App\\Controller\\Admin\\UserController::manageRoles'], ['id'], null, null, false, false, null]],
        655 => [[['_route' => 'admin_users_show', '_controller' => 'App\\Controller\\Admin\\UserController::show'], ['id'], null, null, false, true, null]],
        673 => [[['_route' => 'admin_users_edit', '_controller' => 'App\\Controller\\Admin\\UserController::edit'], ['id'], null, null, false, false, null]],
        701 => [[['_route' => 'admin_users_toggle_status', '_controller' => 'App\\Controller\\Admin\\UserController::toggleStatus'], ['id'], ['POST' => 0], null, false, false, null]],
        732 => [[['_route' => 'admin_users_ldap_unlock', '_controller' => 'App\\Controller\\Admin\\UserController::ldapUnlock'], ['id'], ['POST' => 0], null, false, false, null]],
        758 => [[['_route' => 'admin_users_ldap_reset_password', '_controller' => 'App\\Controller\\Admin\\UserController::ldapResetPassword'], ['id'], ['POST' => 0], null, false, false, null]],
        771 => [[['_route' => 'admin_users_ldap_refresh', '_controller' => 'App\\Controller\\Admin\\UserController::ldapRefresh'], ['id'], ['POST' => 0], null, false, false, null]],
        862 => [[['_route' => 'app_home_root', '_controller' => 'App\\Controller\\HomeController::root'], ['path'], null, null, false, true, null]],
        917 => [[['_route' => 'api_notifications_mark_read', '_controller' => 'App\\Controller\\NotificationController::markAsRead'], ['id'], ['POST' => 0], null, false, false, null]],
        931 => [[['_route' => 'api_notifications_mark_unread', '_controller' => 'App\\Controller\\NotificationController::markAsUnread'], ['id'], ['POST' => 0], null, false, false, null]],
        940 => [[['_route' => 'api_notifications_delete', '_controller' => 'App\\Controller\\NotificationController::delete'], ['id'], ['DELETE' => 0], null, false, true, null]],
        965 => [[['_route' => 'api_notifications_delete_multiple', '_controller' => 'App\\Controller\\NotificationController::deleteMultiple'], [], ['POST' => 0], null, false, false, null]],
        980 => [[['_route' => 'api_notifications_grouped', '_controller' => 'App\\Controller\\NotificationController::grouped'], [], ['GET' => 0], null, false, false, null]],
        1010 => [[['_route' => 'aparatura_pomiarowa_index', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::index'], [], null, null, true, false, null]],
        1044 => [[['_route' => 'aparatura_pomiarowa_equipment_show', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::showEquipment'], ['id'], null, null, false, true, null]],
        1063 => [[['_route' => 'aparatura_pomiarowa_equipment_edit', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::editEquipment'], ['id'], null, null, false, false, null]],
        1084 => [[['_route' => 'aparatura_pomiarowa_equipment_delete', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::deleteEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        1105 => [[['_route' => 'aparatura_pomiarowa_equipment_assign', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::assignEquipment'], ['id'], null, null, false, false, null]],
        1128 => [[['_route' => 'aparatura_pomiarowa_equipment_unassign', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::unassignEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        1160 => [[['_route' => 'aparatura_pomiarowa_equipment_attachment_upload', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::uploadEquipmentAttachment'], ['id'], ['POST' => 0], null, false, false, null]],
        1203 => [[['_route' => 'aparatura_pomiarowa_equipment_attachment_download', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::downloadEquipmentAttachment'], ['id', 'filename'], null, null, false, false, null]],
        1244 => [[['_route' => 'aparatura_pomiarowa_equipment_attachment_delete', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaController::deleteEquipmentAttachment'], ['id', 'filename'], ['POST' => 0], null, false, false, null]],
        1269 => [[['_route' => 'aparatura_pomiarowa_equipment_set_show', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::show'], ['id'], null, null, false, true, null]],
        1288 => [[['_route' => 'aparatura_pomiarowa_equipment_set_edit', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::edit'], ['id'], null, null, false, false, null]],
        1309 => [[['_route' => 'aparatura_pomiarowa_equipment_set_delete', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        1337 => [[['_route' => 'aparatura_pomiarowa_equipment_set_add_equipment', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::addEquipment'], ['id'], null, null, false, false, null]],
        1374 => [[['_route' => 'aparatura_pomiarowa_equipment_set_remove_equipment', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::removeEquipment'], ['id', 'equipmentId'], ['POST' => 0], null, false, false, null]],
        1411 => [[['_route' => 'aparatura_pomiarowa_equipment_set_remove_bulk_equipment', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::removeBulkEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        1443 => [[['_route' => 'aparatura_pomiarowa_equipment_set_attachment_upload', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::uploadAttachment'], ['id'], ['POST' => 0], null, false, false, null]],
        1486 => [[['_route' => 'aparatura_pomiarowa_equipment_set_attachment_download', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::downloadAttachment'], ['id', 'filename'], null, null, false, false, null]],
        1527 => [[['_route' => 'aparatura_pomiarowa_equipment_set_attachment_delete', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::deleteAttachment'], ['id', 'filename'], ['POST' => 0], null, false, false, null]],
        1561 => [[['_route' => 'aparatura_pomiarowa_transfer_prepare', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::prepareTransfer'], ['setId'], ['POST' => 0], null, false, false, null]],
        1584 => [[['_route' => 'aparatura_pomiarowa_transfer_complete', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::completeTransfer'], ['id'], ['POST' => 0], null, false, false, null]],
        1616 => [[['_route' => 'aparatura_pomiarowa_transfer_protocol_download', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::downloadTransferProtocol'], ['id'], null, null, false, false, null]],
        1637 => [[['_route' => 'aparatura_pomiarowa_transfer_return', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::prepareReturnForTransfer'], ['id'], ['POST' => 0], null, false, false, null]],
        1670 => [[['_route' => 'aparatura_pomiarowa_return_prepare', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::prepareReturn'], ['setId'], ['POST' => 0], null, false, false, null]],
        1693 => [[['_route' => 'aparatura_pomiarowa_return_complete', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::completeReturn'], ['id'], ['POST' => 0], null, false, false, null]],
        1725 => [[['_route' => 'aparatura_pomiarowa_return_protocol_download', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaEquipmentSetController::downloadReturnProtocol'], ['id'], null, null, false, false, null]],
        1753 => [[['_route' => 'aparatura_pomiarowa_review_show', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::show'], ['id'], null, null, false, true, null]],
        1772 => [[['_route' => 'aparatura_pomiarowa_review_edit', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::edit'], ['id'], null, null, false, false, null]],
        1803 => [[['_route' => 'aparatura_pomiarowa_review_new_for_equipment', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::newForEquipment'], ['id'], null, null, false, true, null]],
        1823 => [[['_route' => 'aparatura_pomiarowa_review_new_for_set', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::newForEquipmentSet'], ['id'], null, null, false, true, null]],
        1843 => [[['_route' => 'aparatura_pomiarowa_review_send', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::sendReview'], ['id'], ['POST' => 0], null, false, false, null]],
        1864 => [[['_route' => 'aparatura_pomiarowa_review_delete', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::deleteReview'], ['id'], ['POST' => 0], null, false, false, null]],
        1887 => [[['_route' => 'aparatura_pomiarowa_review_complete', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::completeReview'], ['id'], ['POST' => 0], null, false, false, null]],
        1921 => [[['_route' => 'aparatura_pomiarowa_review_attachment_download', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::downloadAttachment'], ['id', 'filename'], ['GET' => 0], null, false, true, null]],
        1949 => [[['_route' => 'aparatura_pomiarowa_review_add_equipment', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::addEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        1986 => [[['_route' => 'aparatura_pomiarowa_review_remove_equipment', '_controller' => 'App\\AparaturaPomiarowa\\Controller\\AparaturaPomiarowaReviewController::removeEquipment'], ['id', 'equipmentId'], ['POST' => 0], null, false, false, null]],
        2033 => [[['_route' => 'asekuracja_equipment_show', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::showEquipment'], ['id'], null, null, false, true, null]],
        2052 => [[['_route' => 'asekuracja_equipment_edit', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::editEquipment'], ['id'], null, null, false, false, null]],
        2073 => [[['_route' => 'asekuracja_equipment_delete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::deleteEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        2094 => [[['_route' => 'asekuracja_equipment_assign', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::assignEquipment'], ['id'], null, null, false, false, null]],
        2117 => [[['_route' => 'asekuracja_equipment_unassign', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::unassignEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        2149 => [[['_route' => 'asekuracja_equipment_attachment_upload', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::uploadEquipmentAttachment'], ['id'], ['POST' => 0], null, false, false, null]],
        2192 => [[['_route' => 'asekuracja_equipment_attachment_download', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::downloadEquipmentAttachment'], ['id', 'filename'], null, null, false, false, null]],
        2233 => [[['_route' => 'asekuracja_equipment_attachment_delete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::deleteEquipmentAttachment'], ['id', 'filename'], ['POST' => 0], null, false, false, null]],
        2258 => [[['_route' => 'asekuracja_equipment_set_show', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::show'], ['id'], null, null, false, true, null]],
        2277 => [[['_route' => 'asekuracja_equipment_set_edit', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::edit'], ['id'], null, null, false, false, null]],
        2298 => [[['_route' => 'asekuracja_equipment_set_delete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        2326 => [[['_route' => 'asekuracja_equipment_set_add_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::addEquipment'], ['id'], null, null, false, false, null]],
        2363 => [[['_route' => 'asekuracja_equipment_set_remove_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::removeEquipment'], ['id', 'equipmentId'], ['POST' => 0], null, false, false, null]],
        2400 => [[['_route' => 'asekuracja_equipment_set_remove_bulk_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::removeBulkEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        2432 => [[['_route' => 'asekuracja_equipment_set_attachment_upload', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::uploadAttachment'], ['id'], ['POST' => 0], null, false, false, null]],
        2475 => [[['_route' => 'asekuracja_equipment_set_attachment_download', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::downloadAttachment'], ['id', 'filename'], null, null, false, false, null]],
        2516 => [[['_route' => 'asekuracja_equipment_set_attachment_delete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::deleteAttachment'], ['id', 'filename'], ['POST' => 0], null, false, false, null]],
        2550 => [[['_route' => 'asekuracja_transfer_prepare', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::prepareTransfer'], ['setId'], ['POST' => 0], null, false, false, null]],
        2573 => [[['_route' => 'asekuracja_transfer_complete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::completeTransfer'], ['id'], ['POST' => 0], null, false, false, null]],
        2605 => [[['_route' => 'asekuracja_transfer_protocol_download', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::downloadTransferProtocol'], ['id'], null, null, false, false, null]],
        2626 => [[['_route' => 'asekuracja_transfer_return', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::prepareReturnForTransfer'], ['id'], ['POST' => 0], null, false, false, null]],
        2659 => [[['_route' => 'asekuracja_return_prepare', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::prepareReturn'], ['setId'], ['POST' => 0], null, false, false, null]],
        2682 => [[['_route' => 'asekuracja_return_complete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::completeReturn'], ['id'], ['POST' => 0], null, false, false, null]],
        2714 => [[['_route' => 'asekuracja_return_protocol_download', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::downloadReturnProtocol'], ['id'], null, null, false, false, null]],
        2742 => [[['_route' => 'asekuracja_review_show', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::show'], ['id'], null, null, false, true, null]],
        2761 => [[['_route' => 'asekuracja_review_edit', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::edit'], ['id'], null, null, false, false, null]],
        2792 => [[['_route' => 'asekuracja_review_new_for_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::newForEquipment'], ['id'], null, null, false, true, null]],
        2812 => [[['_route' => 'asekuracja_review_new_for_set', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::newForEquipmentSet'], ['id'], null, null, false, true, null]],
        2832 => [[['_route' => 'asekuracja_review_send', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::sendReview'], ['id'], ['POST' => 0], null, false, false, null]],
        2853 => [[['_route' => 'asekuracja_review_delete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::deleteReview'], ['id'], ['POST' => 0], null, false, false, null]],
        2876 => [[['_route' => 'asekuracja_review_complete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::completeReview'], ['id'], ['POST' => 0], null, false, false, null]],
        2910 => [[['_route' => 'asekuracja_review_attachment_download', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::downloadAttachment'], ['id', 'filename'], ['GET' => 0], null, false, true, null]],
        2938 => [[['_route' => 'asekuracja_review_add_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::addEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        2975 => [
            [['_route' => 'asekuracja_review_remove_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::removeEquipment'], ['id', 'equipmentId'], ['POST' => 0], null, false, false, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
