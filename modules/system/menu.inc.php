<?php
function _system_menu_may_cache(&$items) {
  $items[] = array('path' => 'system/files', 'title' => t('File download'),
    'callback' => 'file_download',
    'access' => TRUE,
    'type' => MENU_CALLBACK);

  $access = user_access('administer site configuration');

  $items[] = array('path' => 'admin', 'title' => t('Administer'),
    'access' => user_access('access administration pages'),
    'callback' => 'system_main_admin_page',
    'weight' => 9);
  $items[] = array('path' => 'admin/compact', 'title' => t('Compact mode'),
    'access' => user_access('access administration pages'),
    'callback' => 'system_admin_compact_page',
    'type' => MENU_CALLBACK);
  $items[] = array('path' => 'admin/by-task', 'title' => t('By task'),
    'callback' => 'system_main_admin_page',
    'type' => MENU_DEFAULT_LOCAL_TASK);
  $items[] = array('path' => 'admin/by-module', 'title' => t('By module'),
    'callback' => 'system_admin_by_module',
    'type' => MENU_LOCAL_TASK,
    'weight' => 2);

  // menu items that are basically just menu blocks
  $items[] = array(
    'path' => 'admin/settings',
    'title' => t('Site configuration'),
    'description' => t('Adjust basic site configuration options.'),
    'position' => 'right',
    'weight' => -5,
    'callback' => 'system_settings_overview',
    'access' => $access);

  $items[] = array('path' => 'admin/build',
    'title' => t('Site building'),
    'description' => t('Control how your site looks and feels.'),
    'position' => 'right',
    'weight' => -10,
    'callback' => 'system_admin_menu_block_page',
    'access' => $access);

  $items[] = array(
    'path' => 'admin/settings/admin',
    'title' => t('Administration theme'),
    'description' => t('Settings for how your administrative pages should look.'),
    'position' => 'left',
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_admin_theme_settings'),
    'block callback' => 'system_admin_theme_settings',
    'access' => $access);

  // Themes:
  $items[] = array(
    'path' => 'admin/build/themes',
    'title' => t('Themes'),
    'description' => t('Change which theme your site uses or allows users to set.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_themes'),
    'access' => $access);

  $items[] = array(
    'path' => 'admin/build/themes/select',
    'title' => t('List'),
    'description' => t('Select the default theme.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_themes'),
    'access' => $access,
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => -1);

  $items[] = array('path' => 'admin/build/themes/settings',
    'title' => t('Configure'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_theme_settings'),
    'access' => $access,
    'type' => MENU_LOCAL_TASK);

  // Theme configuration subtabs
  $items[] = array('path' => 'admin/build/themes/settings/global', 'title' => t('Global settings'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_theme_settings'),
    'access' => $access,
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => -1);

  foreach (list_themes() as $theme) {
    if ($theme->status) {
      $items[] = array('path' => 'admin/build/themes/settings/'. $theme->name, 'title' => $theme->name,
      'callback' => 'drupal_get_form', 'callback arguments' => array('system_theme_settings', $theme->name),
      'access' => $access, 'type' => MENU_LOCAL_TASK);
    }
  }

  // Modules:
  $items[] = array('path' => 'admin/build/modules',
    'title' => t('Modules'),
    'description' => t('Enable or disable add-on modules for your site.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_modules'),
    'access' => $access);
  $items[] = array('path' => 'admin/build/modules/list',
    'title' => t('List'),
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'access' => $access);
  $items[] = array('path' => 'admin/build/modules/list/confirm',
    'title' => t('List'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_modules'),
    'type' => MENU_CALLBACK,
    'access' => $access);
  $items[] = array('path' => 'admin/build/modules/uninstall',
    'title' => t('Uninstall'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_modules_uninstall'),
    'type' => MENU_LOCAL_TASK,
    'access' => $access);
  $items[] = array('path' => 'admin/build/modules/uninstall/confirm',
    'title' => t('Uninstall'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_modules_uninstall'),
    'type' => MENU_CALLBACK,
    'access' => $access);

  // Settings:
  $items[] = array(
    'path' => 'admin/settings/site-information',
    'title' => t('Site information'),
    'description' => t('Change basic site information, such as the site name, slogan, e-mail address, mission, front page and more.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_site_information_settings'));
  $items[] = array(
    'path' => 'admin/settings/error-reporting',
    'title' => t('Error reporting'),
    'description' => t('Control how Drupal deals with errors including 403/404 errors as well as PHP error reporting.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_error_reporting_settings'));
  $items[] = array(
    'path' => 'admin/settings/performance',
    'title' => t('Performance'),
    'description' => t('Enable or disable page caching for anonymous users, and enable or disable CSS preprocessor.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_performance_settings'));
  $items[] = array(
    'path' => 'admin/settings/file-system',
    'title' => t('File system'),
    'description' => t('Tell Drupal where to store uploaded files and how they are accessed.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_file_system_settings'));
  $items[] = array(
    'path' => 'admin/settings/image-toolkit',
    'title' => t('Image toolkit'),
    'description' => t('Choose which image toolkit to use if you have installed optional toolkits.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_image_toolkit_settings'));
  $items[] = array(
    'path' => 'admin/content/rss-publishing',
    'title' => t('RSS publishing'),
    'description' => t('Configure the number of items per feed and whether feeds should be titles/teasers/full-text.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_rss_feeds_settings'));
  $items[] = array(
    'path' => 'admin/settings/date-time',
    'title' => t('Date and time'),
    'description' => t("Settings for how Drupal displays date and time, as well as the system's default timezone."),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_date_time_settings'));
  $items[] = array(
    'path' => 'admin/settings/site-maintenance',
    'title' => t('Site maintenance'),
    'description' => t('Take the site off-line for maintenance or bring it back online.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_site_maintenance_settings'));
  $items[] = array(
    'path' => 'admin/settings/clean-urls',
    'title' => t('Clean URLs'),
    'description' => t('Enable or disable clean URLs for your site.'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('system_clean_url_settings'));


  // Logs:
  $items[] = array(
    'path' => 'admin/logs',
    'title' => t('Logs'),
    'description' => t('View system logs and other status information.'),
    'callback' => 'system_admin_menu_block_page',
    'weight' => 5,
    'position' => 'left');
  $items[] = array(
    'path' => 'admin/logs/status',
    'title' => t('Status report'),
    'description' => t("Get a status report about your site's operation and any detected problems."),
    'callback' => 'system_status',
    'weight' => 10,
    'access' => $access);
  $items[] = array(
    'path' => 'admin/logs/status/run-cron',
    'title' => t('Run cron'),
    'callback' => 'system_run_cron',
    'type' => MENU_CALLBACK);
  $items[] = array(
    'path' => 'admin/logs/status/php',
    'title' => t('PHP'),
    'callback' => 'system_php',
    'type' => MENU_CALLBACK);
  $items[] = array(
    'path' => 'admin/logs/status/sql',
    'title' => t('SQL'),
    'callback' => 'system_sql',
    'type' => MENU_CALLBACK);
}
