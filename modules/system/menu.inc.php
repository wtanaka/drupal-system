<?php
function _real_system_menu() {
  $items['system/files'] = array(
    'title' => 'File download',
    'page callback' => 'file_download',
    'access callback' => TRUE,
    'type' => MENU_CALLBACK,
  );
  $items['admin'] = array(
    'title' => 'Administer',
    'access arguments' => array('access administration pages'),
    'page callback' => 'system_main_admin_page',
    'weight' => 9,
    'file' => 'system.admin.inc',
  );
  $items['admin/compact'] = array(
    'title' => 'Compact mode',
    'page callback' => 'system_admin_compact_page',
    'access arguments' => array('access administration pages'),
    'type' => MENU_CALLBACK,
    'file' => 'system.admin.inc',
  );
  $items['admin/by-task'] = array(
    'title' => 'By task',
    'page callback' => 'system_main_admin_page',
    'access arguments' => array('access administration pages'),
    'file' => 'system.admin.inc',
    'type' => MENU_DEFAULT_LOCAL_TASK,
  );
  $items['admin/by-module'] = array(
    'title' => 'By module',
    'page callback' => 'system_admin_by_module',
    'access arguments' => array('access administration pages'),
    'file' => 'system.admin.inc',
    'type' => MENU_LOCAL_TASK,
    'weight' => 2,
  );
  $items['admin/content'] = array(
    'title' => 'Content management',
    'description' => "Manage your site's content.",
    'position' => 'left',
    'weight' => -10,
    'page callback' => 'system_admin_menu_block_page',
    'access arguments' => array('access administration pages'),
    'file' => 'system.admin.inc',
  );

  // menu items that are basically just menu blocks
  $items['admin/settings'] = array(
    'title' => 'Site configuration',
    'description' => 'Adjust basic site configuration options.',
    'position' => 'right',
    'weight' => -5,
    'page callback' => 'system_settings_overview',
    'access arguments' => array('access administration pages'),
    'file' => 'system.admin.inc',
  );
  $items['admin/build'] = array(
    'title' => 'Site building',
    'description' => 'Control how your site looks and feels.',
    'position' => 'right',
    'weight' => -10,
    'page callback' => 'system_admin_menu_block_page',
    'access arguments' => array('access administration pages'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/admin'] = array(
    'title' => 'Administration theme',
    'description' => 'Settings for how your administrative pages should look.',
    'position' => 'left',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_admin_theme_settings'),
    'access arguments' => array('administer site configuration'),
    'block callback' => 'system_admin_theme_settings',
    'file' => 'system.admin.inc',
  );
  // Themes:
  $items['admin/build/themes'] = array(
    'title' => 'Themes',
    'description' => 'Change which theme your site uses or allows users to set.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_themes_form', NULL),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/build/themes/select'] = array(
    'title' => 'List',
    'description' => 'Select the default theme.',
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => -1,
  );
  $items['admin/build/themes/settings'] = array(
    'title' => 'Configure',
    'page arguments' => array('system_theme_settings'),
    'access arguments' => array('administer site configuration'),
    'type' => MENU_LOCAL_TASK,
  );
  // Theme configuration subtabs
  $items['admin/build/themes/settings/global'] = array(
    'title' => 'Global settings',
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => -1,
  );

  foreach (list_themes() as $theme) {
    $items['admin/build/themes/settings/'. $theme->name] = array(
      'title' => $theme->info['name'],
      'page arguments' => array('system_theme_settings', $theme->name),
      'type' => MENU_LOCAL_TASK,
      'access callback' => '_system_themes_access',
      'access arguments' => array($theme),
    );
  }

  // Modules:
  $items['admin/build/modules'] = array(
    'title' => 'Modules',
    'description' => 'Enable or disable add-on modules for your site.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_modules'),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/build/modules/list'] = array(
    'title' => 'List',
    'type' => MENU_DEFAULT_LOCAL_TASK,
  );
  $items['admin/build/modules/list/confirm'] = array(
    'title' => 'List',
    'access arguments' => array('administer site configuration'),
    'type' => MENU_CALLBACK,
  );
  $items['admin/build/modules/uninstall'] = array(
    'title' => 'Uninstall',
    'page arguments' => array('system_modules_uninstall'),
    'access arguments' => array('administer site configuration'),
    'type' => MENU_LOCAL_TASK,
  );
  $items['admin/build/modules/uninstall/confirm'] = array(
    'title' => 'Uninstall',
    'access arguments' => array('administer site configuration'),
    'type' => MENU_CALLBACK,
  );

  // Actions:
  $items['admin/settings/actions'] = array(
    'title' => 'Actions',
    'description' => 'Manage the actions defined for your site.',
    'access arguments' => array('administer actions'),
    'page callback' => 'system_actions_manage'
  );
  $items['admin/settings/actions/manage'] = array(
    'title' => 'Manage actions',
    'description' => 'Manage the actions defined for your site.',
    'page callback' => 'system_actions_manage',
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => -2,
  );
  $items['admin/settings/actions/configure'] = array(
    'title' => 'Configure an advanced action',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_actions_configure'),
    'access arguments' => array('administer actions'),
    'type' => MENU_CALLBACK,
  );
  $items['admin/settings/actions/delete/%actions'] = array(
    'title' => 'Delete action',
    'description' => 'Delete an action.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_actions_delete_form', 4),
    'access arguments' => array('administer actions'),
    'type' => MENU_CALLBACK,
  );
  $items['admin/settings/actions/orphan'] = array(
    'title' => 'Remove orphans',
    'page callback' => 'system_actions_remove_orphans',
    'access arguments' => array('administer actions'),
    'type' => MENU_CALLBACK,
  );

  // Settings:
  $items['admin/settings/site-information'] = array(
    'title' => 'Site information',
    'description' => 'Change basic site information, such as the site name, slogan, e-mail address, mission, front page and more.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_site_information_settings'),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/error-reporting'] = array(
    'title' => 'Error reporting',
    'description' => 'Control how Drupal deals with errors including 403/404 errors as well as PHP error reporting.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_error_reporting_settings'),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/logging'] = array(
    'title' => 'Logging and alerts',
    'description' => "Settings for logging and alerts modules. Various modules can route Drupal's system events to different destination, such as syslog, database, email, ...etc.",
    'page callback' => 'system_logging_overview',
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/performance'] = array(
    'title' => 'Performance',
    'description' => 'Enable or disable page caching for anonymous users and set CSS and JS bandwidth optimization options.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_performance_settings'),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/file-system'] = array(
    'title' => 'File system',
    'description' => 'Tell Drupal where to store uploaded files and how they are accessed.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_file_system_settings'),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/image-toolkit'] = array(
    'title' => 'Image toolkit',
    'description' => 'Choose which image toolkit to use if you have installed optional toolkits.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_image_toolkit_settings'),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/content/rss-publishing'] = array(
    'title' => 'RSS publishing',
    'description' => 'Configure the number of items per feed and whether feeds should be titles/teasers/full-text.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_rss_feeds_settings'),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/date-time'] = array(
    'title' => 'Date and time',
    'description' => "Settings for how Drupal displays date and time, as well as the system's default timezone.",
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_date_time_settings'),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/date-time/lookup'] = array(
    'title' => 'Date and time lookup',
    'type' => MENU_CALLBACK,
    'page callback' => 'system_date_time_lookup',
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/site-maintenance'] = array(
    'title' => 'Site maintenance',
    'description' => 'Take the site off-line for maintenance or bring it back online.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_site_maintenance_settings'),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/clean-urls'] = array(
    'title' => 'Clean URLs',
    'description' => 'Enable or disable clean URLs for your site.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('system_clean_url_settings'),
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/settings/clean-urls/check'] = array(
    'title' => 'Clean URL check',
    'page callback' => 'drupal_json',
    'page arguments' => array(array('status' => TRUE)),
    'access callback' => TRUE,
    'type' => MENU_CALLBACK,
  );

  // Reports:
  $items['admin/reports'] = array(
    'title' => 'Reports',
    'description' => 'View reports from system logs and other status information.',
    'page callback' => 'system_admin_menu_block_page',
    'access arguments' => array('access site reports'),
    'weight' => 5,
    'position' => 'left',
    'file' => 'system.admin.inc',
  );
  $items['admin/reports/status'] = array(
    'title' => 'Status report',
    'description' => "Get a status report about your site's operation and any detected problems.",
    'page callback' => 'system_status',
    'weight' => 10,
    'access arguments' => array('administer site configuration'),
    'file' => 'system.admin.inc',
  );
  $items['admin/reports/status/run-cron'] = array(
    'title' => 'Run cron',
    'page callback' => 'system_run_cron',
    'access arguments' => array('administer site configuration'),
    'type' => MENU_CALLBACK,
    'file' => 'system.admin.inc',
  );
  $items['admin/reports/status/php'] = array(
    'title' => 'PHP',
    'page callback' => 'system_php',
    'access arguments' => array('administer site configuration'),
    'type' => MENU_CALLBACK,
    'file' => 'system.admin.inc',
  );
  $items['admin/reports/status/sql'] = array(
    'title' => 'SQL',
    'page callback' => 'system_sql',
    'access arguments' => array('administer site configuration'),
    'type' => MENU_CALLBACK,
    'file' => 'system.admin.inc',
  );
  // Default page for batch operations
  $items['batch'] = array(
    'page callback' => 'system_batch_page',
    'access callback' => TRUE,
    'type' => MENU_CALLBACK,
    'file' => 'system.admin.inc',
  );
  return $items;
}
