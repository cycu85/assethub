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
        '/equipment' => [[['_route' => 'equipment_index', '_controller' => 'App\\Controller\\EquipmentController::index'], null, null, null, true, false, null]],
        '/equipment/new' => [[['_route' => 'equipment_new', '_controller' => 'App\\Controller\\EquipmentController::new'], null, null, null, false, false, null]],
        '/equipment/my' => [[['_route' => 'equipment_my', '_controller' => 'App\\Controller\\EquipmentController::myEquipment'], null, null, null, false, false, null]],
        '/error/access-denied' => [[['_route' => 'error_access_denied', '_controller' => 'App\\Controller\\ErrorController::accessDenied'], null, null, null, false, false, null]],
        '/error/not-found' => [[['_route' => 'error_not_found', '_controller' => 'App\\Controller\\ErrorController::notFound'], null, null, null, false, false, null]],
        '/install' => [[['_route' => 'installer_welcome', '_controller' => 'App\\Controller\\InstallerController::welcome'], null, null, null, true, false, null]],
        '/install/requirements' => [[['_route' => 'installer_requirements', '_controller' => 'App\\Controller\\InstallerController::requirements'], null, null, null, false, false, null]],
        '/install/database' => [[['_route' => 'installer_database', '_controller' => 'App\\Controller\\InstallerController::database'], null, null, null, false, false, null]],
        '/install/admin' => [[['_route' => 'installer_admin', '_controller' => 'App\\Controller\\InstallerController::admin'], null, null, null, false, false, null]],
        '/install/finish' => [[['_route' => 'installer_finish', '_controller' => 'App\\Controller\\InstallerController::finish'], null, null, null, false, false, null]],
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
                    .')'
                .')'
                .'|/equipment/(?'
                    .'|(\\d+)(*:730)'
                    .'|(\\d+)/edit(*:748)'
                    .'|(\\d+)/delete(*:768)'
                    .'|category/(\\d+)(*:790)'
                .')'
                .'|/((?!install|admin|api|login|logout|profile|asekuracja).*)(*:857)'
                .'|/asekuracja/(?'
                    .'|equipment(?'
                        .'|/(?'
                            .'|(\\d+)(*:901)'
                            .'|(\\d+)/edit(*:919)'
                            .'|(\\d+)/delete(*:939)'
                            .'|(\\d+)/assign(*:959)'
                            .'|(\\d+)/unassign(*:981)'
                            .'|(\\d+)/attachment/upload(*:1012)'
                            .'|(\\d+)/attachment/([^/]++)/download(*:1055)'
                            .'|(\\d+)/attachment/([^/]++)/delete(*:1096)'
                        .')'
                        .'|\\-sets/(?'
                            .'|(\\d+)(*:1121)'
                            .'|(\\d+)/edit(*:1140)'
                            .'|(\\d+)/delete(*:1161)'
                            .'|(\\d+)/equipment/add(*:1189)'
                            .'|(\\d+)/equipment/(\\d+)/remove(*:1226)'
                            .'|(\\d+)/equipment/remove\\-bulk(*:1263)'
                            .'|(\\d+)/attachment/upload(*:1295)'
                            .'|(\\d+)/attachment/([^/]++)/download(*:1338)'
                            .'|(\\d+)/attachment/([^/]++)/delete(*:1379)'
                            .'|transfer/(?'
                                .'|(\\d+)/prepare(*:1413)'
                                .'|(\\d+)/complete(*:1436)'
                                .'|(\\d+)/protocol/download(*:1468)'
                                .'|(\\d+)/return(*:1489)'
                            .')'
                            .'|return/(?'
                                .'|(\\d+)/prepare(*:1522)'
                                .'|(\\d+)/complete(*:1545)'
                                .'|(\\d+)/protocol/download(*:1577)'
                            .')'
                        .')'
                    .')'
                    .'|reviews/(?'
                        .'|(\\d+)(*:1605)'
                        .'|(\\d+)/edit(*:1624)'
                        .'|new/equipment(?'
                            .'|/(\\d+)(*:1655)'
                            .'|\\-set/(\\d+)(*:1675)'
                        .')'
                        .'|(\\d+)/send(*:1695)'
                        .'|(\\d+)/delete(*:1716)'
                        .'|(\\d+)/complete(*:1739)'
                        .'|(\\d+)/attachment/([^/]++)(*:1773)'
                        .'|(\\d+)/equipment/add(*:1801)'
                        .'|(\\d+)/equipment/(\\d+)/remove(*:1838)'
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
        730 => [[['_route' => 'equipment_show', '_controller' => 'App\\Controller\\EquipmentController::show'], ['id'], null, null, false, true, null]],
        748 => [[['_route' => 'equipment_edit', '_controller' => 'App\\Controller\\EquipmentController::edit'], ['id'], null, null, false, false, null]],
        768 => [[['_route' => 'equipment_delete', '_controller' => 'App\\Controller\\EquipmentController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        790 => [[['_route' => 'equipment_by_category', '_controller' => 'App\\Controller\\EquipmentController::byCategory'], ['id'], null, null, false, true, null]],
        857 => [[['_route' => 'app_home_root', '_controller' => 'App\\Controller\\HomeController::root'], ['path'], null, null, false, true, null]],
        901 => [[['_route' => 'asekuracja_equipment_show', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::showEquipment'], ['id'], null, null, false, true, null]],
        919 => [[['_route' => 'asekuracja_equipment_edit', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::editEquipment'], ['id'], null, null, false, false, null]],
        939 => [[['_route' => 'asekuracja_equipment_delete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::deleteEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        959 => [[['_route' => 'asekuracja_equipment_assign', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::assignEquipment'], ['id'], null, null, false, false, null]],
        981 => [[['_route' => 'asekuracja_equipment_unassign', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::unassignEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        1012 => [[['_route' => 'asekuracja_equipment_attachment_upload', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::uploadEquipmentAttachment'], ['id'], ['POST' => 0], null, false, false, null]],
        1055 => [[['_route' => 'asekuracja_equipment_attachment_download', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::downloadEquipmentAttachment'], ['id', 'filename'], null, null, false, false, null]],
        1096 => [[['_route' => 'asekuracja_equipment_attachment_delete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\AsekuracyjnyController::deleteEquipmentAttachment'], ['id', 'filename'], ['POST' => 0], null, false, false, null]],
        1121 => [[['_route' => 'asekuracja_equipment_set_show', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::show'], ['id'], null, null, false, true, null]],
        1140 => [[['_route' => 'asekuracja_equipment_set_edit', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::edit'], ['id'], null, null, false, false, null]],
        1161 => [[['_route' => 'asekuracja_equipment_set_delete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        1189 => [[['_route' => 'asekuracja_equipment_set_add_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::addEquipment'], ['id'], null, null, false, false, null]],
        1226 => [[['_route' => 'asekuracja_equipment_set_remove_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::removeEquipment'], ['id', 'equipmentId'], ['POST' => 0], null, false, false, null]],
        1263 => [[['_route' => 'asekuracja_equipment_set_remove_bulk_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::removeBulkEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        1295 => [[['_route' => 'asekuracja_equipment_set_attachment_upload', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::uploadAttachment'], ['id'], ['POST' => 0], null, false, false, null]],
        1338 => [[['_route' => 'asekuracja_equipment_set_attachment_download', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::downloadAttachment'], ['id', 'filename'], null, null, false, false, null]],
        1379 => [[['_route' => 'asekuracja_equipment_set_attachment_delete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::deleteAttachment'], ['id', 'filename'], ['POST' => 0], null, false, false, null]],
        1413 => [[['_route' => 'asekuracja_transfer_prepare', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::prepareTransfer'], ['setId'], ['POST' => 0], null, false, false, null]],
        1436 => [[['_route' => 'asekuracja_transfer_complete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::completeTransfer'], ['id'], ['POST' => 0], null, false, false, null]],
        1468 => [[['_route' => 'asekuracja_transfer_protocol_download', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::downloadTransferProtocol'], ['id'], null, null, false, false, null]],
        1489 => [[['_route' => 'asekuracja_transfer_return', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::prepareReturnForTransfer'], ['id'], ['POST' => 0], null, false, false, null]],
        1522 => [[['_route' => 'asekuracja_return_prepare', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::prepareReturn'], ['setId'], ['POST' => 0], null, false, false, null]],
        1545 => [[['_route' => 'asekuracja_return_complete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::completeReturn'], ['id'], ['POST' => 0], null, false, false, null]],
        1577 => [[['_route' => 'asekuracja_return_protocol_download', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\EquipmentSetController::downloadReturnProtocol'], ['id'], null, null, false, false, null]],
        1605 => [[['_route' => 'asekuracja_review_show', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::show'], ['id'], null, null, false, true, null]],
        1624 => [[['_route' => 'asekuracja_review_edit', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::edit'], ['id'], null, null, false, false, null]],
        1655 => [[['_route' => 'asekuracja_review_new_for_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::newForEquipment'], ['id'], null, null, false, true, null]],
        1675 => [[['_route' => 'asekuracja_review_new_for_set', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::newForEquipmentSet'], ['id'], null, null, false, true, null]],
        1695 => [[['_route' => 'asekuracja_review_send', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::sendReview'], ['id'], ['POST' => 0], null, false, false, null]],
        1716 => [[['_route' => 'asekuracja_review_delete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::deleteReview'], ['id'], ['POST' => 0], null, false, false, null]],
        1739 => [[['_route' => 'asekuracja_review_complete', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::completeReview'], ['id'], ['POST' => 0], null, false, false, null]],
        1773 => [[['_route' => 'asekuracja_review_attachment_download', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::downloadAttachment'], ['id', 'filename'], ['GET' => 0], null, false, true, null]],
        1801 => [[['_route' => 'asekuracja_review_add_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::addEquipment'], ['id'], ['POST' => 0], null, false, false, null]],
        1838 => [
            [['_route' => 'asekuracja_review_remove_equipment', '_controller' => 'App\\AsekuracyjnySPM\\Controller\\ReviewController::removeEquipment'], ['id', 'equipmentId'], ['POST' => 0], null, false, false, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
