<?php

/**
 * Implementation of hook_help().
 */
function _system_help($section) {
  global $base_url;

  switch ($section) {
    case 'admin/help#system':
      $output = '<p>'. t('The system module provides system-wide defaults such as running jobs at a particular time, and storing web pages to improve efficiency. The ability to run scheduled jobs makes administering the web site more usable, as administrators do not have to manually start jobs. The storing of web pages, or caching, allows the site to efficiently re-use web pages and improve web site performance. The system module provides control over preferences, behaviours including visual and operational settings.') .'</p>';
      $output .= '<p>'. t('Some modules require regularly scheduled actions, such as cleaning up logfiles. Cron, which stands for chronograph, is a periodic command scheduler executing commands at intervals specified in seconds. It can be used to control the execution of daily, weekly and monthly jobs (or anything with a period measured in seconds). The aggregator module periodically updates feeds using cron. Ping periodically notifies services of new content on your site. Search periodically indexes the content on your site. Automating tasks is one of the best ways to keep a system running smoothly, and if most of your administration does not require your direct involvement, cron is an ideal solution. Cron can, if necessary, also be run manually.') .'</p>';
      $output .= '<p>'. t("There is a caching mechanism which stores dynamically generated web pages in a database. By caching a web page, the system module does not have to create the page each time someone wants to view it, instead it takes only one SQL query to display it, reducing response time and the server's load. Only pages requested by <em>anonymous</em> users are cached. In order to reduce server load and save bandwidth, the system module stores and sends cached pages compressed.") .'</p>';
      $output .= '<p>'. t('For more information please read the configuration and customization handbook <a href="@system">System page</a>.', array('@system' => 'http://drupal.org/handbook/modules/system/')) .'</p>';
      return $output;
    case 'admin':
      return '<p>'. t('Welcome to the administration section. Here you may control how your site functions.') .'</p>';
    case 'admin/by-module':
      return '<p>'. t('This page shows you all available administration tasks for each module.') .'</p>';
    case 'admin/build/themes':
      return '<p>'. t('Select which themes are available to your users and specify the default theme. To configure site-wide display settings, click the "configure" task above. Alternately, to override these settings in a specific theme, click the "configure" link for the corresponding theme. Note that different themes may have different regions available for rendering content like blocks. If you want consistency in what your users see, you may wish to enable only one theme.') .'</p>';
    case 'admin/build/themes/settings':
      return '<p>'. t('These options control the default display settings for your entire site, across all themes. Unless they have been overridden by a specific theme, these settings will be used.') .'</p>';
    case 'admin/build/themes/settings/'. arg(4):
      $reference = explode('.', arg(4), 2);
      $theme = array_pop($reference);
      return '<p>'. t('These options control the display settings for the <code>%template</code> theme. When your site is displayed using this theme, these settings will be used. By clicking "Reset to defaults," you can choose to use the <a href="@global">global settings</a> for this theme.', array('%template' => $theme, '@global' => url('admin/build/themes/settings'))) .'</p>';
    case 'admin/build/modules':
      return t('<p>Modules are plugins for Drupal that extend its core functionality. Here you can select which modules are enabled. Click on the name of the module in the navigation menu for their individual configuration pages. Once a module is enabled, new <a href="@permissions">permissions</a> might be made available. Modules can automatically be temporarily disabled to reduce server load when your site becomes extremely busy by enabling the throttle.module and checking throttle. The auto-throttle functionality must be enabled on the <a href="@throttle">throttle configuration page</a> after having enabled the throttle module.</p>
<p>It is important that <a href="@update-php">update.php</a> is run every time a module is updated to a newer version.</p><p>You can find all administration tasks belonging to a particular module on the <a href="@by-module">administration by module page</a>.</p>', array('@permissions' => url('admin/user/access'), '@throttle' => url('admin/settings/throttle'), '@update-php' => $base_url .'/update.php', '@by-module' => url('admin/by-module')));
    case 'admin/build/modules/uninstall':
      return '<p>'. t('The uninstall process removes all data related to a module. To uninstall a module, you must first disable it. Not all modules support this feature.') .'</p>';
    case 'admin/logs/status':
      return '<p>'. t("Here you can find a short overview of your Drupal site's parameters as well as any problems detected with your installation. It is useful to copy/paste this information when you need support.") .'</p>';
  }
}

/**
 * Provide the administration overview page.
 */
function _system_main_admin_page($arg = NULL) {
  // If we received an argument, they probably meant some other page.
  // Let's 404 them since the menu system cannot be told we do not
  // accept arguments.
  if (isset($arg) && substr($arg, 0, 3) != 'by-') {
    return drupal_not_found();
  }

  // Check for status report errors.
  if (system_status(TRUE)) {
    drupal_set_message(t('One or more problems were detected with your Drupal installation. Check the <a href="@status">status report</a> for more information.', array('@status' => url('admin/logs/status'))), 'error');
  }


  $menu = menu_get_item(NULL, 'admin');
  usort($menu['children'], '_menu_sort');
  foreach ($menu['children'] as $mid) {
    $block = menu_get_item($mid);
    if ($block['block callback'] && function_exists($block['block callback'])) {
      $arguments = isset($block['block arguments']) ? $block['block arguments'] : array();
      $block['content'] .= call_user_func_array($block['block callback'], $arguments);
    }
    $block['content'] .= theme('admin_block_content', system_admin_menu_block($block));
    $blocks[] = $block;
  }

  return theme('admin_page', $blocks);
}

/**
 * Provide a single block on the administration overview page.
 */
function system_admin_menu_block($block) {
  $content = array();
  if (is_array($block['children'])) {
    usort($block['children'], '_menu_sort');
    foreach ($block['children'] as $mid) {
      $item = menu_get_item($mid);
      if (($item['type'] & MENU_VISIBLE_IN_TREE) && _menu_item_is_accessible($mid)) {
        $content[] = $item;
      }
    }
  }
  return $content;
}

/**
 * Provide a single block from the administration menu as a page.
 * This function is often a destination for these blocks.
 * For example, 'admin/content/types' needs to have a destination to be valid
 * in the Drupal menu system, but too much information there might be
 * hidden, so we supply the contents of the block.
 */
function _system_admin_menu_block_page() {
  $menu = menu_get_item(NULL, $_GET['q']);
  $content = system_admin_menu_block($menu);

  $output = theme('admin_block_content', $content);
  return $output;
}

/**
 * This function allows selection of the theme to show in administration sections.
 */
function _system_admin_theme_settings() {
  $themes = system_theme_data();
  ksort($themes);
  $options[0] = t('System default');
  foreach ($themes as $theme) {
    $options[$theme->name] = $theme->name;
  }

  $form['admin_theme'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#title' => t('Administration theme'),
    '#description' => t('Choose which theme the administration pages should display in. If you choose "System default" the administration pages will use the same theme as the rest of the site.'),
    '#default_value' => variable_get('admin_theme', '0'),
  );

  // In order to give it our own submit, we have to give it the default submit
  // too because the presence of a #submit will prevent the default #submit
  // from being used. Also we want ours first.
  $form['#submit']['system_admin_theme_submit'] = array();
  $form['#submit']['system_settings_form_submit'] = array();
  return system_settings_form($form);
}

function system_admin_theme_submit($form_id, $form_values) {
  // If we're changing themes, make sure the theme has its blocks initialized.
  if ($form_values['admin_theme'] != variable_get('admin_theme', '0')) {
    $result = db_query("SELECT status FROM {blocks} WHERE theme = '%s'", $form_values['admin_theme']);
    if (!db_num_rows($result)) {
      system_initialize_theme_blocks($form_values['admin_theme']);
    }
  }
}

/**
 * Returns a fieldset containing the theme select form.
 *
 * @param $description
 *    description of the fieldset
 * @param $default_value
 *    default value of theme radios
 * @param $weight
 *    weight of the fieldset
 * @return
 *    a form array
 */
function _system_theme_select_form($description = '', $default_value = '', $weight = 0) {
  if (user_access('select different theme')) {
    foreach (list_themes() as $theme) {
      if ($theme->status) {
        $enabled[] = $theme;
      }
    }

    if (count($enabled) > 1) {
      ksort($enabled);

      $form['themes'] = array(
        '#type' => 'fieldset',
        '#title' => t('Theme configuration'),
        '#description' => $description,
        '#collapsible' => TRUE,
        '#theme' => 'system_theme_select_form'
      );

      foreach ($enabled as $info) {
        // For the default theme, revert to an empty string so the user's theme updates when the site theme is changed.
        $info->key = $info->name == variable_get('theme_default', 'garland') ? '' : $info->name;

        $info->screenshot = dirname($info->filename) .'/screenshot.png';
        $screenshot = file_exists($info->screenshot) ? theme('image', $info->screenshot, t('Screenshot for %theme theme', array('%theme' => $info->name)), '', array('class' => 'screenshot'), FALSE) : t('no screenshot');

        $form['themes'][$info->key]['screenshot'] = array('#value' => $screenshot);
        $form['themes'][$info->key]['description'] = array('#type' => 'item', '#title' => $info->name,  '#value' => dirname($info->filename) . ($info->name == variable_get('theme_default', 'garland') ? '<br /> <em>'. t('(site default theme)') .'</em>' : ''));
        $options[$info->key] = '';
      }

      $form['themes']['theme'] = array('#type' => 'radios', '#options' => $options, '#default_value' => $default_value ? $default_value : '');
      $form['#weight'] = $weight;
      return $form;
    }
  }
}

function theme_system_theme_select_form($form) {
  foreach (element_children($form) as $key) {
    $row = array();
    if (is_array($form[$key]['description'])) {
      $row[] = drupal_render($form[$key]['screenshot']);
      $row[] = drupal_render($form[$key]['description']);
      $row[] = drupal_render($form['theme'][$key]);
    }
    $rows[] = $row;
  }

  $header = array(t('Screenshot'), t('Name'), t('Selected'));
  $output = theme('table', $header, $rows);
  return $output;
}

function _system_site_information_settings() {
  $form['site_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => variable_get('site_name', 'Drupal'),
    '#description' => t('The name of this web site.'),
    '#required' => TRUE
  );
  $form['site_mail'] = array(
    '#type' => 'textfield',
    '#title' => t('E-mail address'),
    '#default_value' => variable_get('site_mail', ini_get('sendmail_from')),
    '#description' => t('A valid e-mail address to be used as the "From" address by the auto-mailer during registration, new password requests, notifications, etc.  To lessen the likelihood of e-mail being marked as spam, this e-mail address should use the same domain as the website.'),
    '#required' => TRUE,
  );
  $form['site_slogan'] = array(
    '#type' => 'textfield',
    '#title' => t('Slogan'),
    '#default_value' => variable_get('site_slogan', ''),
    '#description' => t('The slogan of this website. Some themes display a slogan when available.')
  );

  $form['site_mission'] = array(
    '#type' => 'textarea',
    '#title' => t('Mission'),
    '#default_value' => variable_get('site_mission', ''),
    '#description' => t('Your site\'s mission statement or focus.')
  );
  $form['site_footer'] = array(
    '#type' => 'textarea',
    '#title' => t('Footer message'),
    '#default_value' => variable_get('site_footer', ''),
    '#description' => t('This text will be displayed at the bottom of each page. Useful for adding a copyright notice to your pages.')
  );
  $form['anonymous'] = array(
    '#type' => 'textfield',
    '#title' => t('Anonymous user'),
    '#default_value' => variable_get('anonymous', t('Anonymous')),
    '#description' => t('The name used to indicate anonymous users.')
  );
  $form['site_frontpage'] = array(
    '#type' => 'textfield',
    '#title' => t('Default front page'),
    '#default_value' => variable_get('site_frontpage', 'node'),
    '#size' => 40,
    '#description' => t('The home page displays content from this relative URL. If unsure, specify "node".'),
    '#field_prefix' => url(NULL, NULL, NULL, TRUE) . (variable_get('clean_url', 0) ? '' : '?q=')
  );

  return system_settings_form($form);
}

function _system_clean_url_settings() {
  // We check for clean URL support using an image on the client side.
  $form['clean_url'] = array(
    '#type' => 'radios',
    '#title' => t('Clean URLs'),
    '#default_value' => variable_get('clean_url', 0),
    '#options' => array(t('Disabled'), t('Enabled')),
    '#description' => t('This option makes Drupal emit "clean" URLs (i.e. without <code>?q=</code> in the URL.)'),
  );

  if (!variable_get('clean_url', 0)) {
    if (strpos(request_uri(), '?q=') !== FALSE) {
      $form['clean_url']['#description'] .= t(' Before enabling clean URLs, you must perform a test to determine if your server is properly configured. If you are able to see this page again after clicking the "Run the clean URL test" link, the test has succeeded and the radio buttons above will be available. If instead you are directed to a "Page not found" error, you will need to change the configuration of your server. The <a href="@handbook">handbook page on Clean URLs</a> has additional troubleshooting information. !run-test', array('@handbook' => 'http://drupal.org/node/15365', '!run-test' => '<a href ="'. base_path() .'admin/settings/clean-urls">'. t('Run the clean URL test') .'</a>'));
      $form['clean_url']['#disabled'] = TRUE;
    }
    else {
      $form['clean_url']['#description'] .= t(' You have successfully demonstrated that clean URLs work on your server. You may enable/disable them as you wish.');
      $form['#collapsed'] = FALSE;
    }
  }

  return system_settings_form($form);
}

function _system_error_reporting_settings() {

  $form['site_403'] = array(
    '#type' => 'textfield',
    '#title' => t('Default 403 (access denied) page'),
    '#default_value' => variable_get('site_403', ''),
    '#size' => 40,
    '#description' => t('This page is displayed when the requested document is denied to the current user. If unsure, specify nothing.'),
    '#field_prefix' => url(NULL, NULL, NULL, TRUE) . (variable_get('clean_url', 0) ? '' : '?q=')
  );

  $form['site_404'] = array(
    '#type' => 'textfield',
    '#title' => t('Default 404 (not found) page'),
    '#default_value' =>  variable_get('site_404', ''),
    '#size' => 40,
    '#description' => t('This page is displayed when no other content matches the requested document. If unsure, specify nothing.'),
    '#field_prefix' => url(NULL, NULL, NULL, TRUE) . (variable_get('clean_url', 0) ? '' : '?q=')
  );

  $form['error_level'] = array(
    '#type' => 'select', '#title' => t('Error reporting'), '#default_value' => variable_get('error_level', 1),
    '#options' => array(t('Write errors to the log'), t('Write errors to the log and to the screen')),
    '#description' =>  t('Where Drupal, PHP and SQL errors are logged. On a production server it is recommended that errors are only written to the error log. On a test server it can be helpful to write logs to the screen.')
  );

  $period = drupal_map_assoc(array(3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200), 'format_interval');
  $period['1000000000'] = t('Never');
  $form['watchdog_clear'] = array(
    '#type' => 'select',
    '#title' => t('Discard log entries older than'),
    '#default_value' => variable_get('watchdog_clear', 604800),
    '#options' => $period,
    '#description' => t('The time log entries should be kept. Older entries will be automatically discarded. Requires crontab.')
  );

  return system_settings_form($form);
}

function _system_performance_settings() {

  $description = '<p>'. t("The normal cache mode is suitable for most sites and does not cause any side effects. The aggressive cache mode causes Drupal to skip the loading (init) and unloading (exit) of enabled modules when serving a cached page. This results in an additional performance boost but can cause unwanted side effects.") .'</p>';

  $problem_modules = array_unique(array_merge(module_implements('init'), module_implements('exit')));
  sort($problem_modules);

  if (count($problem_modules) > 0) {
    $description .= '<p>'. t('<strong class="error">The following enabled modules are incompatible with aggressive mode caching and might not function properly: %modules</strong>', array('%modules' => implode(', ', $problem_modules))) .'.</p>';
  }
  else {
    $description .= '<p>'. t('<strong class="ok">Currently, all enabled modules are compatible with the aggressive caching policy.</strong> Please note, if you use aggressive caching and enable new modules, you will need to check this page again to ensure compatibility.') .'</p>';
  }
  $form['page_cache'] = array(
    '#type' => 'fieldset',
    '#title' => t('Page cache'),
    '#description' => t('Enabling the cache will offer a significant performance boost. Drupal can store and send compressed cached pages requested by <em>anonymous</em> users. By caching a web page, Drupal does not have to construct the page each time someone wants to view it.'),
  );

  $form['page_cache']['cache'] = array(
    '#type' => 'radios',
    '#title' => t('Caching mode'),
    '#default_value' => variable_get('cache', CACHE_DISABLED),
    '#options' => array(CACHE_DISABLED => t('Disabled'), CACHE_NORMAL => t('Normal (recommended, no side effects)'), CACHE_AGGRESSIVE => t('Aggressive (experts only, possible side effects)')),
    '#description' => $description
  );

  $period = drupal_map_assoc(array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400), 'format_interval');
  $period[0] = t('none');
  $form['page_cache']['cache_lifetime'] = array(
    '#type' => 'select',
    '#title' => t('Minimum cache lifetime'),
    '#default_value' => variable_get('cache_lifetime', 0),
    '#options' => $period,
    '#description' => t('On high-traffic sites it can become necessary to enforce a minimum cache lifetime. The minimum cache lifetime is the minimum amount of time that will go by before the cache is emptied and recreated. A larger minimum cache lifetime offers better performance, but users will not see new content for a longer period of time.')
  );

  $form['bandwidth_optimizations'] = array(
    '#type' => 'fieldset',
    '#title' => t('Bandwidth optimizations'),
    '#description' => t('These options can help reduce both the size and number of requests made to your website. This can reduce the server load, the bandwidth used, and the average page loading time for your visitors.')
  );

  $directory = file_directory_path();
  $is_writable = is_dir($directory) && is_writable($directory) && (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC) == FILE_DOWNLOADS_PUBLIC);
  $form['bandwidth_optimizations']['preprocess_css'] = array(
    '#type' => 'radios',
    '#title' => t('Aggregate and compress CSS files'),
    '#default_value' => intval(variable_get('preprocess_css', FALSE) && $is_writable),
    '#disabled' => !$is_writable,
    '#options' => array(t('Disabled'), t('Enabled')),
    '#description' => t("Some Drupal modules include their own CSS files. When these modules are enabled, each module's CSS file adds an additional HTTP request to the page, which can increase the load time of each page. These HTTP requests can also slightly increase server load. It is recommended to only turn this option on when your site is in production, as it can interfere with theme development. This option is disabled if you have not set up your files directory, or if your download method is set to private."),
  );

  $form['#submit']['system_settings_form_submit'] = array();
  $form['#submit']['drupal_clear_css_cache'] = array();

  return system_settings_form($form);
}

function _system_file_system_settings() {

  $form['file_directory_path'] = array(
    '#type' => 'textfield',
    '#title' => t('File system path'),
    '#default_value' => file_directory_path(),
    '#maxlength' => 255,
    '#description' => t('A file system path where the files will be stored. This directory has to exist and be writable by Drupal. If the download method is set to public this directory has to be relative to the Drupal installation directory, and be accessible over the web. When download method is set to private this directory should not be accessible over the web. Changing this location after the site has been in use will cause problems so only change this setting on an existing site if you know what you are doing.'),
    '#after_build' => array('system_check_directory'),
  );

  $form['file_directory_temp'] = array(
    '#type' => 'textfield',
    '#title' => t('Temporary directory'),
    '#default_value' => file_directory_temp(),
    '#maxlength' => 255,
    '#description' => t('Location where uploaded files will be kept during previews. Relative paths will be resolved relative to the Drupal installation directory.'),
    '#after_build' => array('system_check_directory'),
  );

  $form['file_downloads'] = array(
    '#type' => 'radios',
    '#title' => t('Download method'),
    '#default_value' => variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC),
    '#options' => array(FILE_DOWNLOADS_PUBLIC => t('Public - files are available using HTTP directly.'), FILE_DOWNLOADS_PRIVATE => t('Private - files are transferred by Drupal.')),
    '#description' => t('If you want any sort of access control on the downloading of files, this needs to be set to <em>private</em>. You can change this at any time, however all download URLs will change and there may be unexpected problems so it is not recommended.')
  );

  return system_settings_form($form);
}

function _system_image_toolkit_settings() {
  $toolkits_available = image_get_available_toolkits();
  if (count($toolkits_available) > 1) {
    $form['image_toolkit'] = array(
      '#type' => 'radios',
      '#title' => t('Select an image processing toolkit'),
      '#default_value' => variable_get('image_toolkit', image_get_toolkit()),
      '#options' => $toolkits_available
    );
  }
  else {
    $form['image_toolkit'] = array('#value' => '<p>'. t("No image toolkits found. Drupal will use PHP's built-in GD library for image handling.") .'</p>');
  }
  $form['image_toolkit_settings'] = image_toolkit_invoke('settings');
  return system_settings_form($form);
}

function _system_rss_feeds_settings() {

  $form['feed_default_items'] = array(
    '#type' => 'select',
    '#title' => t('Number of items per feed'),
    '#default_value' => variable_get('feed_default_items', 10),
    '#options' => drupal_map_assoc(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30)),
    '#description' => t('The default number of items to include in a feed.')
  );
  $form['feed_item_length'] = array(
    '#type' => 'select',
    '#title' => t('Display of XML feed items'),
    '#default_value' => variable_get('feed_item_length', 'teaser'),
    '#options' => array('title' => t('Titles only'), 'teaser' => t('Titles plus teaser'), 'fulltext' => t('Full text')),
    '#description' => t('Global setting for the length of XML feed items that are output by default.')
  );

  return system_settings_form($form);
}

function _system_date_time_settings() {
  // Date settings:
  $zones = _system_zonelist();

  // Date settings: possible date formats
  $dateshort = array('Y-m-d H:i', 'm/d/Y - H:i', 'd/m/Y - H:i', 'Y/m/d - H:i',
           'd.m.Y - H:i', 'm/d/Y - g:ia', 'd/m/Y - g:ia', 'Y/m/d - g:ia',
           'M j Y - H:i', 'j M Y - H:i', 'Y M j - H:i',
           'M j Y - g:ia', 'j M Y - g:ia', 'Y M j - g:ia');
  $datemedium = array('D, Y-m-d H:i', 'D, m/d/Y - H:i', 'D, d/m/Y - H:i',
          'D, Y/m/d - H:i', 'F j, Y - H:i', 'j F, Y - H:i', 'Y, F j - H:i',
          'D, m/d/Y - g:ia', 'D, d/m/Y - g:ia', 'D, Y/m/d - g:ia',
          'F j, Y - g:ia', 'j F Y - g:ia', 'Y, F j - g:ia', 'j. F Y - G:i');
  $datelong = array('l, F j, Y - H:i', 'l, j F, Y - H:i', 'l, Y,  F j - H:i',
        'l, F j, Y - g:ia', 'l, j F Y - g:ia', 'l, Y,  F j - g:ia', 'l, j. F Y - G:i');

  // Date settings: construct choices for user
  foreach ($dateshort as $f) {
    $dateshortchoices[$f] = format_date(time(), 'custom', $f);
  }
  foreach ($datemedium as $f) {
    $datemediumchoices[$f] = format_date(time(), 'custom', $f);
  }
  foreach ($datelong as $f) {
    $datelongchoices[$f] = format_date(time(), 'custom', $f);
  }

  $form['date_default_timezone'] = array(
    '#type' => 'select',
    '#title' => t('Default time zone'),
    '#default_value' => variable_get('date_default_timezone', 0),
    '#options' => $zones,
    '#description' => t('Select the default site time zone.')
  );

  $form['configurable_timezones'] = array(
    '#type' => 'radios',
    '#title' => t('Configurable time zones'),
    '#default_value' => variable_get('configurable_timezones', 1),
    '#options' => array(t('Disabled'), t('Enabled')),
    '#description' => t('Enable or disable user-configurable time zones. When enabled, users can set their own time zone and dates will be updated accordingly.')
  );

  $form['date_format_short'] = array(
    '#type' => 'select',
    '#title' => t('Short date format'),
    '#default_value' => variable_get('date_format_short', $dateshort[1]),
    '#options' => $dateshortchoices,
    '#description' => t('The short format of date display.')
  );

  $form['date_format_medium'] = array(
    '#type' => 'select',
    '#title' => t('Medium date format'),
    '#default_value' => variable_get('date_format_medium', $datemedium[1]),
    '#options' => $datemediumchoices,
    '#description' => t('The medium sized date display.')
  );

  $form['date_format_long'] = array(
    '#type' => 'select',
    '#title' => t('Long date format'),
    '#default_value' => variable_get('date_format_long', $datelong[0]),
    '#options' => $datelongchoices,
    '#description' => t('Longer date format used for detailed display.')
  );

  $form['date_first_day'] = array(
    '#type' => 'select',
    '#title' => t('First day of week'),
    '#default_value' => variable_get('date_first_day', 0),
    '#options' => array(0 => t('Sunday'), 1 => t('Monday'), 2 => t('Tuesday'), 3 => t('Wednesday'), 4 => t('Thursday'), 5 => t('Friday'), 6 => t('Saturday')),
    '#description' => t('The first day of the week for calendar views.')
  );

  return system_settings_form($form);
}

function _system_site_maintenance_settings() {

  $form['site_offline'] = array(
    '#type' => 'radios',
    '#title' => t('Site status'),
    '#default_value' => variable_get('site_offline', 0),
    '#options' => array(t('Online'), t('Off-line')),
    '#description' => t('When set to "Online", all visitors will be able to browse your site normally. When set to "Off-line", only users with the "administer site configuration" permission will be able to access your site to perform maintenance; all other visitors will see the site off-line message configured below. Authorized users can log in during "Off-line" mode directly via the <a href="@user-login">user login</a> page.', array('@user-login' => url('user'))),
  );

  $form['site_offline_message'] = array(
    '#type' => 'textarea',
    '#title' => t('Site off-line message'),
    '#default_value' => variable_get('site_offline_message', t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => variable_get('site_name', 'Drupal')))),
    '#description' => t('Message to show visitors when the site is in off-line mode.')
  );

  return system_settings_form($form);
}

/**
 * Checks the existence of the directory specified in $form_element. This
 * function is called from the system_settings form to check both the
 * file_directory_path and file_directory_temp directories. If validation
 * fails, the form element is flagged with an error from within the
 * file_check_directory function.
 *
 * @param $form_element
 *   The form element containing the name of the directory to check.
 */
function system_check_directory($form_element) {
  file_check_directory($form_element['#value'], FILE_CREATE_DIRECTORY, $form_element['#parents'][0]);
  return $form_element;
}

/**
 * Collect data about all currently available themes
 */
function _system_theme_data() {
  include_once './includes/install.inc';

  // Find themes
  $themes = drupal_system_listing('\.theme$', 'themes');

  // Find theme engines
  $engines = drupal_system_listing('\.engine$', 'themes/engines');

  // can't iterate over array itself as it uses a copy of the array items
  foreach (array_keys($themes) as $key) {
    drupal_get_filename('theme', $themes[$key]->name, $themes[$key]->filename);
    drupal_load('theme', $themes[$key]->name);
    $themes[$key]->owner = $themes[$key]->filename;
    $themes[$key]->prefix = $key;
  }

  // Remove all theme engines from the system table
  db_query("DELETE FROM {system} WHERE type = 'theme_engine'");

  foreach ($engines as $engine) {
    // Insert theme engine into system table
    drupal_get_filename('theme_engine', $engine->name, $engine->filename);
    drupal_load('theme_engine', $engine->name);
    db_query("INSERT INTO {system} (name, type, filename, status, throttle, bootstrap) VALUES ('%s', '%s', '%s', %d, %d, %d)", $engine->name, 'theme_engine', $engine->filename, 1, 0, 0);

    // Add templates to the site listing
    foreach (call_user_func($engine->name .'_templates') as $template) {
      // Do not double-insert templates with theme files in their directory,
      // but do register their engine data.
      if (array_key_exists($template->name, $themes)) {
        $themes[$template->name]->template = TRUE;
        $themes[$template->name]->owner = $engine->filename;
        $themes[$template->name]->prefix = $engine->name;
      }
      else {
        $template->template = TRUE;
        $template->name = basename(dirname($template->filename));
        $template->owner = $engine->filename;
        $template->prefix = $engine->name;

        $themes[$template->name] = $template;
      }
    }
  }

  // Find styles in each theme's directory.
  foreach ($themes as $theme) {
    foreach (file_scan_directory(dirname($theme->filename), 'style.css$') as $style) {
      $style->style = TRUE;
      $style->template = isset($theme->template) ? $theme->template : FALSE;
      $style->name = basename(dirname($style->filename));
      $style->owner = $theme->filename;
      $style->prefix = $theme->template ? $theme->prefix : $theme->name;
      // do not double-insert styles with theme files in their directory
      if (array_key_exists($style->name, $themes)) {
        continue;
      }
      $themes[$style->name] = $style;
    }
  }

  // Extract current files from database.
  system_get_files_database($themes, 'theme');

  db_query("DELETE FROM {system} WHERE type = 'theme'");

  foreach ($themes as $theme) {
    db_query("INSERT INTO {system} (name, description, type, filename, status, throttle, bootstrap) VALUES ('%s', '%s', '%s', '%s', %d, %d, %d)", $theme->name, $theme->owner, 'theme', $theme->filename, $theme->status, 0, 0);
  }

  return $themes;
}

function system_theme_settings_submit($form_id, $form_values) {
  $op = isset($_POST['op']) ? $_POST['op'] : '';
  $key = $form_values['var'];

  // Exclude unnecessary elements.
  unset($form_values['var'], $form_values['submit'], $form_values['reset'], $form_values['form_id']);

  if ($op == t('Reset to defaults')) {
    variable_del($key);
    drupal_set_message(t('The configuration options have been reset to their default values.'));
  }
  else {
    variable_set($key, $form_values);
    drupal_set_message(t('The configuration options have been saved.'));
  }

  cache_clear_all();
}

/**
 * Menu callback; displays a listing of all themes.
 */
function _system_themes() {

  drupal_clear_css_cache();
  $themes = system_theme_data();
  ksort($themes);

  foreach ($themes as $info) {
    $info->screenshot = dirname($info->filename) .'/screenshot.png';
    $screenshot = file_exists($info->screenshot) ? theme('image', $info->screenshot, t('Screenshot for %theme theme', array('%theme' => $info->name)), '', array('class' => 'screenshot'), FALSE) : t('no screenshot');

    $form[$info->name]['screenshot'] = array('#value' => $screenshot);
    $form[$info->name]['description'] = array('#type' => 'item', '#title' => $info->name,  '#value' => dirname($info->filename));
    $options[$info->name] = '';
    if ($info->status) {
      $status[] = $info->name;
    }
    if ($info->status && (function_exists($info->prefix .'_settings') || function_exists($info->prefix .'_features'))) {
      $form[$info->name]['operations'] = array('#value' => l(t('configure'), 'admin/build/themes/settings/'. $info->name) );
    }
    else {
      // Dummy element for drupal_render. Cleaner than adding a check in the theme function.
      $form[$info->name]['operations'] = array();
    }
  }

  $form['status'] = array('#type' => 'checkboxes', '#options' => $options, '#default_value' => $status);
  $form['theme_default'] = array('#type' => 'radios', '#options' => $options, '#default_value' => variable_get('theme_default', 'garland'));
  $form['buttons']['submit'] = array('#type' => 'submit', '#value' => t('Save configuration') );
  $form['buttons']['reset'] = array('#type' => 'submit', '#value' => t('Reset to defaults') );

  return $form;
}

function theme_system_themes($form) {
  foreach (element_children($form) as $key) {
    $row = array();
    if (is_array($form[$key]['description'])) {
      $row[] = drupal_render($form[$key]['screenshot']);
      $row[] = drupal_render($form[$key]['description']);
      $row[] = array('data' => drupal_render($form['status'][$key]), 'align' => 'center');
      if ($form['theme_default']) {
        $row[] = array('data' => drupal_render($form['theme_default'][$key]), 'align' => 'center');
        $row[] = array('data' => drupal_render($form[$key]['operations']), 'align' => 'center');
      }
    }
    $rows[] = $row;
  }

  $header = array(t('Screenshot'), t('Name'), t('Enabled'), t('Default'), t('Operations'));
  $output = theme('table', $header, $rows);
  $output .= drupal_render($form);
  return $output;
}

function system_themes_submit($form_id, $form_values) {

  db_query("UPDATE {system} SET status = 0 WHERE type = 'theme'");

  if ($form_values['op'] == t('Save configuration')) {
    if (is_array($form_values['status'])) {
      foreach ($form_values['status'] as $key => $choice) {
        // Always enable the default theme, despite its status checkbox being checked:
        if ($choice || $form_values['theme_default'] == $key) {
          system_initialize_theme_blocks($key);
          db_query("UPDATE {system} SET status = 1 WHERE type = 'theme' and name = '%s'", $key);
        }
      }
    }
    if (($admin_theme = variable_get('admin_theme', '0')) != '0' && $admin_theme != $form_values['theme_default']) {
      drupal_set_message(t('Please note that the <a href="!admin_theme_page">administration theme</a> is still set to the %admin_theme theme; consequently, the theme on this page remains unchanged. All non-administrative sections of the site, however, will show the selected %selected_theme theme by default.', array(
        '!admin_theme_page' => url('admin/settings/admin'),
        '%admin_theme' => $admin_theme,
        '%selected_theme' => $form_values['theme_default'],
      )));
    }
    variable_set('theme_default', $form_values['theme_default']);
  }
  else {
    variable_del('theme_default');
    db_query("UPDATE {system} SET status = 1 WHERE type = 'theme' AND name = 'garland'");
  }

  menu_rebuild();
  drupal_set_message(t('The configuration options have been saved.'));
  return 'admin/build/themes';
}


/**
 * Menu callback; provides module enable/disable interface.
 *
 * Modules can be enabled or disabled and set for throttling if the throttle module is enabled.
 * The list of modules gets populated by module.info files, which contain each module's name,
 * description and dependencies.
 * @see _module_parse_info_file for information on module.info descriptors.
 *
 * Dependency checking is performed to ensure that a module cannot be enabled if the module has
 * disabled dependencies and also to ensure that the module cannot be disabled if the module has
 * enabled dependents.
 *
 * @return
 *   The form array.
 */
function _system_modules($form_values = NULL) {
  // Get current list of modules.
  $files = module_rebuild_cache();

  uasort($files, 'system_sort_modules_by_info_name');

  if ($confirm_form = system_modules_confirm_form($files, $form_values)) {
    return $confirm_form;
  }

  // Store module list for validation callback.
  $form['validation_modules'] = array('#type' => 'value', '#value' => $files);

  // Create storage for disabled modules as browser will disable checkboxes.
  $form['disabled_modules'] = array('#type' => 'value', '#value' => array());

  // Array for disabling checkboxes in callback system_module_disable.
  $disabled = array();
  // Traverse the files retrieved and build the form.
  foreach ($files as $filename => $file) {
    $form['name'][$filename] = array('#value' => $file->info['name']);
    $form['version'][$filename] = array('#value' => $file->info['version']);
    $form['description'][$filename] = array('#value' => t($file->info['description']));
    $options[$filename] = '';
    if ($file->status) {
      $status[] = $file->name;
    }
    if ($file->throttle) {
      $throttle[] = $file->name;
    }

    $dependencies = array();
    // Check for missing dependencies.
    if (is_array($file->info['dependencies'])) {
      foreach ($file->info['dependencies'] as $dependency) {
        if (!isset($files[$dependency]) || !$files[$dependency]->status) {
          if (isset($files[$dependency])) {
            $dependencies[] = $files[$dependency]->info['name'] . t(' (<span class="admin-disabled">disabled</span>)');
          }
          else {
            $dependencies[] = drupal_ucfirst($dependency) . t(' (<span class="admin-missing">missing</span>)');
            $disabled[] = $filename;
            $form['disabled_modules']['#value'][$filename] = FALSE;
          }
        }
        else {
          $dependencies[] = $files[$dependency]->info['name'] . t(' (<span class="admin-enabled">enabled</span>)');
        }
      }

      // Add text for dependencies.
      if (!empty($dependencies)) {
        $form['description'][$filename]['dependencies'] = array(
          '#value' => t('Depends on: !dependencies', array('!dependencies' => implode(', ', $dependencies))),
          '#prefix' => '<div class="admin-dependencies">',
          '#suffix' => '</div>',
        );
      }
    }

    // Mark dependents disabled so user can not remove modules being depended on.
    $dependents = array();
    if (is_array($file->info['dependents'])) {
      foreach ($file->info['dependents'] as $dependent) {
        if ($files[$dependent]->status == 1) {
          $dependents[] = $files[$dependent]->info['name'] . t(' (<span class="admin-enabled">enabled</span>)');
          $disabled[] = $filename;
          $form['disabled_modules']['#value'][$filename] = TRUE;
        }
        else {
          $dependents[] = $files[$dependent]->info['name'] . t(' (<span class="admin-disabled">disabled</span>)');
        }
      }
    }

    // Add text for enabled dependents.
    if (!empty($dependents)) {
      $form['description'][$filename]['required'] = array(
        '#value' => t('Required by: !required', array('!required' => implode(', ', $dependents))),
        '#prefix' => '<div class="admin-required">',
        '#suffix' => '</div>',
      );
    }
  }

  // Merge in required modules.
  $modules_required = array('block', 'filter', 'node', 'system', 'user', 'watchdog');
  foreach ($modules_required as $required) {
    $disabled[] = $required;
    $form['disabled_modules']['#value'][$required] = TRUE;
  }

  // Handle status checkboxes, including overriding
  // the generated checkboxes for required modules.
  $form['status'] = array(
    '#type' => 'checkboxes',
    '#default_value' => $status,
    '#options' => $options,
    '#process' => array(
      'expand_checkboxes' => array(),
      'system_modules_disable' => array($disabled),
    ),
  );

  // Handle throttle checkboxes, including overriding the
  // generated checkboxes for required modules.
  if (module_exists('throttle')) {
    $form['throttle'] = array(
      '#type' => 'checkboxes',
      '#default_value' => $throttle,
      '#options' => $options,
      '#process' => array(
        'expand_checkboxes' => array(),
        'system_modules_disable' => array(array_merge($modules_required, array('throttle'))),
      ),
    );
  }

  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  $form['#multistep'] = TRUE;
  $form['#action'] = url('admin/build/modules/list/confirm');

  return $form;
}

/**
 * Form process callback function to disable check boxes.
 */
function system_modules_disable($form, $edit, $disabled) {
  foreach ($disabled as $key) {
    $form[$key]['#attributes']['disabled'] = 'disabled';
  }
  return $form;
}

function system_modules_confirm_form($modules, $form_values = array()) {
  $form = array();
  $items = array();

  // Check values for submitted dependency errors.
  if ($dependencies = system_module_build_dependencies($modules, $form_values)) {
    // preserve the already switched on modules
    foreach ($modules as $name => $module) {
      if ($module->status) {
        $form['status'][$name] = array('#type' => 'hidden', '#value' => 1);
      }
    }

    $form['validation_modules'] = array('#type' => 'value', '#value' => $modules);
    $form['status']['#tree'] = TRUE;
    foreach ($dependencies as $name => $missing_dependencies) {
      $form['status'][$name] = array('#type' => 'hidden', '#value' => 1);
      foreach ($missing_dependencies as $k => $dependency) {
        $form['status'][$dependency] = array('#type' => 'hidden', '#value' => 1);
        $info = $modules[$dependency]->info;
        $missing_dependencies[$k] = $info['name'] ? $info['name'] : drupal_ucfirst($dependency);
      }
      $t_argument = array(
        '%module' => $modules[$name]->info['name'],
        '%dependencies' => implode(', ', $missing_dependencies),
      );
      $items[] = strtr(format_plural(count($missing_dependencies), 'You must enable the %dependencies module to install %module.', 'You must enable the %dependencies modules to install %module.'), $t_argument);
    }
    $form['text'] = array('#value' => theme('item_list', $items));
  }

  if ($form) {
    // Set some default form values
    $form = confirm_form(
      $form,
      t('Some required modules must be enabled'),
      'admin/build/modules',
      t('Would you like to continue with enabling the above?'),
      t('Continue'),
      t('Cancel'));
    return $form;
  }
}

function system_module_build_dependencies($modules, $form_values) {
  static $dependencies;

  if (!isset($dependencies) && isset($form_values) && is_array($form_values)) {
    $dependencies = array();
    foreach ($modules as $name => $module) {
      // If the module is disabled, will be switched on and it has dependencies.
      if (!$module->status && isset($form_values['status'][$name]) && $form_values['status'][$name] && isset($module->info['dependencies'])) {
        foreach ($module->info['dependencies'] as $dependency) {
          if (!$form_values['status'][$dependency] && isset($modules[$dependency])) {
            if (!isset($dependencies[$name])) {
              $dependencies[$name] = array();
            }
            $dependencies[$name][] = $dependency;
          }
        }
      }
    }
  }
  return $dependencies;
}

/**
 * Submit callback; handles modules form submission.
 */
function system_modules_submit($form_id, $form_values) {
  include_once './includes/install.inc';
  $new_modules = array();

  // Merge in disabled active modules since they should be enabled.
  // They don't appear because disabled checkboxes are not submitted
  // by browsers.
  $form_values['status'] = array_merge($form_values['status'], $form_values['disabled_modules']);

  // Check values for dependency that we can't install.
  if ($dependencies = system_module_build_dependencies($form_values['validation_modules'], $form_values)) {
    // These are the modules that depend on existing modules.
    foreach (array_keys($dependencies) as $name) {
      $form_values['status'][$name] = 0;
    }
  }

  $enable_modules = array();
  $disable_modules = array();
  foreach ($form_values['status'] as $key => $choice) {
    if ($choice) {
      if (drupal_get_installed_schema_version($key) == SCHEMA_UNINSTALLED) {
        $new_modules[] = $key;
      }
      else {
        $enable_modules[] = $key;
      }
    }
    else {
      $disable_modules[] = $key;
    }
  }

  $old_module_list = module_list();

  if (!empty($enable_modules)) {
    module_enable($enable_modules);
  }
  if (!empty($disable_modules)) {
    module_disable($disable_modules);
  }

  // Install new modules.
  foreach ($new_modules as $key => $module) {
    if (!drupal_check_module($module)) {
      unset($new_modules[$key]);
    }
  }
  drupal_install_modules($new_modules);

  $current_module_list = module_list(TRUE, FALSE);

  if (is_array($form_values['throttle'])) {
    foreach ($form_values['throttle'] as $key => $choice) {
      db_query("UPDATE {system} SET throttle = %d WHERE type = 'module' and name = '%s'", $choice ? 1 : 0, $key);
    }
  }

  if ($old_module_list != $current_module_list) {
    menu_rebuild();
    node_types_rebuild();
    drupal_set_message(t('The configuration options have been saved.'));
  }

  // If there where unmet dependencies and they haven't confirmed don't redirect.
  if ($dependencies && !isset($form_values['confirm'])) {
    return FALSE;
  }

  drupal_clear_css_cache();

  return 'admin/build/modules';
}


/**
 * Theme call back for the modules form.
 */
function theme_system_modules($form) {
  if (isset($form['confirm'])) {
    return drupal_render($form);
  }

  // Individual table headers.
  $header = array(t('Enabled'));
  if (module_exists('throttle')) {
    $header[] = t('Throttle');
  }
  $header[] = t('Name');
  $header[] = t('Version');
  $header[] = t('Description');

  // Pull package information from module list and start grouping modules.
  $modules = $form['validation_modules']['#value'];
  foreach ($modules as $module) {
    if (!isset($module->info['package']) || !$module->info['package']) {
      $module->info['package'] = t('Other');
    }
    $packages[$module->info['package']][$module->name] = $module->info;
  }
  ksort($packages);

  // Display packages.
  $output = '';
  foreach ($packages as $package => $modules) {
    $rows = array();
    foreach ($modules as $key => $module) {
      $row = array();
      $row[] = array('data' => drupal_render($form['status'][$key]), 'align' => 'center');

      if (module_exists('throttle')) {
        $row[] = array('data' => drupal_render($form['throttle'][$key]), 'align' => 'center');
      }
      $row[] = '<strong>'. drupal_render($form['name'][$key]) .'</strong>';
      $row[] = drupal_render($form['version'][$key]);
      $row[] = array('data' => drupal_render($form['description'][$key]), 'class' => 'description');
      $rows[] = $row;
    }
    $fieldset = array(
      '#title' => t($package),
      '#collapsible' => TRUE,
      '#collapsed' => ($package == 'Core - required'),
      '#value' => theme('table', $header, $rows, array('class' => 'package')),
    );
    $output .= theme('fieldset', $fieldset);
  }

  $output .= drupal_render($form);
  return $output;
}


/**
 * Uninstall functions
 */

/**
 * Builds a form of currently disabled modules.
 *
 * @param
 *   $form_values Submitted form values.
 * @return
 *   A form array representing the currently disabled modules.
 */
function _system_modules_uninstall($form_values = NULL) {
  // Make sure the install API is available.
  include_once './includes/install.inc';

  // Display the confirm form if any modules have been submitted.
  if ($confirm_form = system_modules_uninstall_confirm_form($form_values)) {
    return $confirm_form;
  }

  $form = array();

  // Pull all disabled modules from the system table.
  $disabled_modules = db_query("SELECT name, filename FROM {system} WHERE type = 'module' AND status = 0 AND schema_version > %d ORDER BY name", SCHEMA_UNINSTALLED);
  while ($module = db_fetch_object($disabled_modules)) {

    // Grab the .info file and set name and description.
    $info = _module_parse_info_file(dirname($module->filename) .'/'. $module->name .'.info');

    // Load the .install file, and check for an uninstall hook.
    // If the hook exists, the module can be uninstalled.
    module_load_install($module->name);
    if (module_hook($module->name, 'uninstall')) {
      $form['modules'][$module->name]['name'] = array('#value' => $info['name'] ? $info['name'] : $module->name);
      $form['modules'][$module->name]['description'] = array('#value' => t($info['description']));
      $options[$module->name] = '';
    }
  }

  // Only build the rest of the form if there are any modules available to uninstall.
  if (count($options)) {
    $form['uninstall'] = array(
      '#type' => 'checkboxes',
      '#options' => $options,
    );
    $form['buttons']['submit'] = array(
      '#type' => 'button',
      '#value' => t('Uninstall'),
    );
    $form['#multistep'] = TRUE;
    $form['#action'] = url('admin/build/modules/uninstall/confirm');
  }

  return $form;
}

/**
 * Confirm uninstall of selected modules.
 *
 * @param
 *   $form_values Submitted form values.
 * @return
 *   A form array representing modules to confirm.
 */
function system_modules_uninstall_confirm_form($form_values) {
  // Nothing to build.
  if (!isset($form_values)) {
    return;
  }

  // Construct the hidden form elements and list items.
  foreach (array_filter($form_values['uninstall']) as $module => $value) {
    $info = _module_parse_info_file(dirname(drupal_get_filename('module', $module)) .'/'. $module .'.info');
    $uninstall[] = $info['name'];
    $form['uninstall'][$module] = array('#type' => 'hidden',
      '#value' => 1,
    );
  }

  // Display a confirm form if modules have been selected.
  if (isset($uninstall)) {
    $form['uninstall']['#tree'] = TRUE;
    $form['#multistep'] = TRUE;
    $form['modules'] = array('#value' => '<p>'. t('The following modules will be completely uninstalled from your site, and <em>all data from these modules will be lost</em>!') .'</p>'. theme('item_list', $uninstall));
    $form = confirm_form(
      $form,
      t('Confirm uninstall'),
      'admin/build/modules/uninstall',
      t('Would you like to continue with uninstalling the above?'),
      t('Uninstall'),
      t('Cancel'));
    return $form;
  }
}

/**
 * Themes a table of currently disabled modules.
 *
 * @param
 *   $form The form array representing the currently disabled modules.
 * @return
 *   An HTML string representing the table.
 */
function theme_system_modules_uninstall($form) {
  // No theming for the confirm form.
  if (isset($form['confirm'])) {
    return drupal_render($form);
  }

  // Table headers.
  $header = array(t('Uninstall'),
    t('Name'),
    t('Description'),
  );

  // Display table.
  $rows = array();
  foreach (element_children($form['modules']) as $module) {
    $rows[] = array(
      array('data' => drupal_render($form['uninstall'][$module]), 'align' => 'center'),
      '<strong>'. drupal_render($form['modules'][$module]['name']) .'</strong>',
      array('data' => drupal_render($form['modules'][$module]['description']), 'class' => 'description'),
    );
  }

  // Only display table if there are modules that can be uninstalled.
  if (!count($rows)) {
    $rows[] = array(array('data' => t('No modules are available to uninstall.'), 'colspan' => '3', 'align' => 'center', 'class' => 'message'));
  }

  $output  = theme('table', $header, $rows);
  $output .= drupal_render($form);

  return $output;
}

/**
 * Validates the submitted uninstall form.
 *
 * @param
 *   $form_id The form ID.
 * @param
 *   $form_values Submitted form values.
 */
function system_modules_uninstall_validate($form_id, $form_values) {
  // Form submitted, but no modules selected.
  if (!count(array_filter($form_values['uninstall']))) {
    drupal_set_message(t('No modules selected.'), 'error');
    drupal_goto('admin/build/modules/uninstall');
  }
}


/**
 * Processes the submitted uninstall form.
 *
 * @param
 *   $form_id The form ID.
 * @param
 *   $form_values Submitted form values.
 */
function system_modules_uninstall_submit($form_id, $form_values) {
  // Make sure the install API is available.
  include_once './includes/install.inc';

  // Call the uninstall routine for each selected module.
  foreach (array_filter($form_values['uninstall']) as $module => $value) {
    drupal_uninstall_module($module);
  }
  drupal_set_message(t('The selected modules have been uninstalled.'));
  drupal_goto('admin/build/modules/uninstall');
}

/**
 * Menu callback: run cron manually.
 */
function _system_run_cron() {
   // Run cron manually
   if (drupal_cron_run()) {
     drupal_set_message(t('Cron ran successfully'));
   }
   else {
     drupal_set_message(t('Cron run failed'));
   }

   drupal_goto('admin/logs/status');
}

function _system_sql($data, $keys) {
  $rows = array();
  foreach ($keys as $key => $explanation) {
    if (isset($data[$key])) {
      $rows[] = array(check_plain($key), check_plain($data[$key]), $explanation);
    }
  }

  return theme('table', array(t('Variable'), t('Value'), t('Description')), $rows);
}

/**
 * Menu callback: return information about PHP.
 */
function real_system_sql() {

  $result = db_query("SHOW STATUS");
  while ($entry = db_fetch_object($result)) {
   $data[$entry->Variable_name] = $entry->Value;
  }

  $output  = '<h2>'. t('Command counters') .'</h2>';
  $output .= _system_sql($data, array(
   'Com_select' => t('The number of <code>SELECT</code>-statements.'),
   'Com_insert' => t('The number of <code>INSERT</code>-statements.'),
   'Com_update' => t('The number of <code>UPDATE</code>-statements.'),
   'Com_delete' => t('The number of <code>DELETE</code>-statements.'),
   'Com_lock_tables' => t('The number of table locks.'),
   'Com_unlock_tables' => t('The number of table unlocks.')
  ));

  $output .= '<h2>'. t('Query performance') .'</h2>';
  $output .= _system_sql($data, array(
   'Select_full_join' => t('The number of joins without an index; should be zero.'),
   'Select_range_check' => t('The number of joins without an index; should be zero.'),
   'Sort_scan' => t('The number of sorts done without using an index; should be zero.'),
   'Table_locks_immediate' => t('The number of times a lock could be acquired immediately.'),
   'Table_locks_waited' => t('The number of times the server had to wait for a lock.')
  ));

  $output .= '<h2>'. t('Query cache information') .'</h2>';
  $output .= '<p>'. t('The MySQL query cache can improve performance of your site by storing the result of queries.  Then, if an identical query is received later, the MySQL server retrieves the result from the query cache rather than parsing and executing the statement again.') .'</p>';
  $output .= _system_sql($data, array(
   'Qcache_queries_in_cache' => t('The number of queries in the query cache.'),
   'Qcache_hits' => t('The number of times that MySQL found previous results in the cache.'),
   'Qcache_inserts' => t('The number of times that MySQL added a query to the cache (misses).'),
   'Qcache_lowmem_prunes' => t('The number of times that MySQL had to remove queries from the cache because it ran out of memory.  Ideally should be zero.')
  ));

  return $output;
}

/**
 * Menu callback: displays the site status report. Can also be used as a pure check.
 *
 * @param $check
 *   If true, only returns a boolean whether there are system status errors.
 */
function _system_status($check = FALSE) {
  // Load .install files
  include_once './includes/install.inc';
  drupal_load_updates();

  // Check run-time requirements and status information
  $requirements = module_invoke_all('requirements', 'runtime');
  usort($requirements, '_system_sort_requirements');

  if ($check) {
    return drupal_requirements_severity($requirements) == REQUIREMENT_ERROR;
  }

  return theme('status_report', $requirements);
}

/**
 * Helper function to sort requirements.
 */
function _system_sort_requirements($a, $b) {
  return (isset($a['weight']) || isset($b['weight'])) ? $a['weight'] - $b['weight'] : strcmp($a['title'], $b['title']);
}

/**
 * Theme status report
 */
function theme_status_report(&$requirements) {
  $i = 0;
  $output = '<table class="system-status-report">';
  foreach ($requirements as $requirement) {
    if ($requirement['#type'] == '') {
      $class = ++$i % 2 == 0 ? 'even' : 'odd';

      $classes = array(
        REQUIREMENT_INFO => 'info',
        REQUIREMENT_OK => 'ok',
        REQUIREMENT_WARNING => 'warning',
        REQUIREMENT_ERROR => 'error',
      );
      $class = $classes[(int)$requirement['severity']] .' '. $class;

      // Output table row(s)
      if ($requirement['description']) {
        $output .= '<tr class="'. $class .' merge-down"><th>'. $requirement['title'] .'</th><td>'. $requirement['value'] .'</td></tr>';
        $output .= '<tr class="'. $class .' merge-up"><td colspan="2">'. $requirement['description'] .'</td></tr>';
      }
      else {
        $output .= '<tr class="'. $class .'"><th>'. $requirement['title'] .'</th><td>'. $requirement['value'] .'</td></tr>';
      }
    }
  }

  $output .= '</table>';
  return $output;
}

/**
 * Menu callback; displays a module's settings page.
 */
function _system_settings_overview() {

  // Check database setup if necessary
  if (function_exists('db_check_setup') && empty($_POST)) {
    db_check_setup();
  }

  $menu = menu_get_item(NULL, 'admin/settings');
  $content = system_admin_menu_block($menu);

  $output = theme('admin_block_content', $content);

  return $output;
}

/**
 * Menu callback; display theme configuration for entire site and individual themes.
 */
function _system_theme_settings($key = '') {
  $directory_path = file_directory_path();
  file_check_directory($directory_path, FILE_CREATE_DIRECTORY, 'file_directory_path');

  // Default settings are defined in theme_get_settings() in includes/theme.inc
  if ($key) {
    $settings = theme_get_settings($key);
    $var = str_replace('/', '_', 'theme_'. $key .'_settings');
    $themes = system_theme_data();
    $features = function_exists($themes[$key]->prefix .'_features') ? call_user_func($themes[$key]->prefix .'_features') : array();
  }
  else {
    $settings = theme_get_settings('');
    $var = 'theme_settings';
  }

  $form['var'] = array('#type' => 'hidden', '#value' => $var);

  // Check for a new uploaded logo, and use that instead.
  if ($file = file_check_upload('logo_upload')) {
    if ($info = image_get_info($file->filepath)) {
      $parts = pathinfo($file->filename);
      $filename = ($key) ? str_replace('/', '_', $key) .'_logo.'. $parts['extension'] : 'logo.'. $parts['extension'];

      if ($file = file_save_upload('logo_upload', $filename, 1)) {
        $_POST['default_logo'] = 0;
        $_POST['logo_path'] = $file->filepath;
        $_POST['toggle_logo'] = 1;
      }
    }
    else {
      form_set_error('file_upload', t('Only JPEG, PNG and GIF images are allowed to be used as logos.'));
    }
  }

  // Check for a new uploaded favicon, and use that instead.
  if ($file = file_check_upload('favicon_upload')) {
    $parts = pathinfo($file->filename);
    $filename = ($key) ? str_replace('/', '_', $key) .'_favicon.'. $parts['extension'] : 'favicon.'. $parts['extension'];

    if ($file = file_save_upload('favicon_upload', $filename, 1)) {
      $_POST['default_favicon'] = 0;
      $_POST['favicon_path'] = $file->filepath;
      $_POST['toggle_favicon'] = 1;
    }
  }

  // Toggle settings
  $toggles = array(
    'toggle_logo'                 => t('Logo'),
    'toggle_name'                 => t('Site name'),
    'toggle_slogan'               => t('Site slogan'),
    'toggle_mission'              => t('Mission statement'),
    'toggle_node_user_picture'    => t('User pictures in posts'),
    'toggle_comment_user_picture' => t('User pictures in comments'),
    'toggle_search'               => t('Search box'),
    'toggle_favicon'              => t('Shortcut icon')
  );

  // Some features are not always available
  $disabled = array();
  if (!variable_get('user_pictures', 0)) {
    $disabled['toggle_node_user_picture'] = TRUE;
    $disabled['toggle_comment_user_picture'] = TRUE;
  }
  if (!module_exists('search')) {
    $disabled['toggle_search'] = TRUE;
  }

  $form['theme_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Toggle display'),
    '#description' => t('Enable or disable the display of certain page elements.'),
  );
  foreach ($toggles as $name => $title) {
    if ((!$key) || in_array($name, $features)) {
      // disable search box if search.module is disabled
      $form['theme_settings'][$name] = array('#type' => 'checkbox', '#title' => $title, '#default_value' => $settings[$name]);
      if (isset($disabled[$name])) {
        $form['theme_settings'][$name]['#disabled'] = TRUE;
      }
    }
  }

  // System wide only settings.
  if (!$key) {
    // Create neat 2-column layout for the toggles
    $form['theme_settings'] += array(
      '#prefix' => '<div class="theme-settings-left">',
      '#suffix' => '</div>',
    );

    // Toggle node display.
    $node_types = node_get_types('names');
    if ($node_types) {
      $form['node_info'] = array(
        '#type' => 'fieldset',
        '#title' => t('Display post information on'),
        '#description' =>  t('Enable or disable the <em>submitted by Username on date</em> text when displaying posts of the following type.'),
        '#prefix' => '<div class="theme-settings-right">',
        '#suffix' => '</div>',
      );
      foreach ($node_types as $type => $name) {
        $form['node_info']["toggle_node_info_$type"] = array('#type' => 'checkbox', '#title' => check_plain($name), '#default_value' => $settings["toggle_node_info_$type"]);
      }
    }
  }

  // Logo settings
  if ((!$key) || in_array('toggle_logo', $features)) {
    $form['logo'] = array(
      '#type' => 'fieldset',
      '#title' => t('Logo image settings'),
      '#description' => t('If toggled on, the following logo will be displayed.'),
      '#attributes' => array('class' => 'theme-settings-bottom'),
    );
    $form['logo']["default_logo"] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the default logo'),
      '#default_value' => $settings['default_logo'],
      '#tree' => FALSE,
      '#description' => t('Check here if you want the theme to use the logo supplied with it.')
    );
    $form['logo']['logo_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to custom logo'),
      '#default_value' => $settings['logo_path'],
      '#description' => t('The path to the file you would like to use as your logo file instead of the default logo.'));

    $form['logo']['logo_upload'] = array(
      '#type' => 'file',
      '#title' => t('Upload logo image'),
      '#maxlength' => 40,
      '#description' => t("If you don't have direct file access to the server, use this field to upload your logo.")
    );
  }

  // Icon settings
  if ((!$key) || in_array('toggle_favicon', $features)) {
    $form['favicon'] = array(
      '#type' => 'fieldset',
      '#title' => t('Shortcut icon settings'),
      '#description' => t("Your shortcut icon or 'favicon' is displayed in the address bar and bookmarks of most browsers.")
    );
    $form['favicon']['default_favicon'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the default shortcut icon.'),
      '#default_value' => $settings['default_favicon'],
      '#description' => t('Check here if you want the theme to use the default shortcut icon.')
    );
    $form['favicon']['favicon_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to custom icon'),
      '#default_value' =>  $settings['favicon_path'],
      '#description' => t('The path to the image file you would like to use as your custom shortcut icon.')
    );

    $form['favicon']['favicon_upload'] = array(
      '#type' => 'file',
      '#title' => t('Upload icon image'),
      '#description' => t("If you don't have direct file access to the server, use this field to upload your shortcut icon.")
    );
  }

  if ($key) {
    // Template-specific settings
    $function = $themes[$key]->prefix .'_settings';
    if (function_exists($function)) {
      if ($themes[$key]->template) {
        // file is a template or a style of a template
        $form['specific'] = array('#type' => 'fieldset', '#title' => t('Engine-specific settings'), '#description' => t('These settings only exist for all the templates and styles based on the %engine theme engine.', array('%engine' => $themes[$key]->prefix)));
      }
      else {
        // file is a theme or a style of a theme
        $form['specific'] = array('#type' => 'fieldset', '#title' => t('Theme-specific settings'), '#description' => t('These settings only exist for the %theme theme and all the styles based on it.', array('%theme' => $themes[$key]->prefix)));
      }
      $group = $function();
      $form['specific'] = array_merge($form['specific'], (is_array($group) ? $group : array()));
    }
  }
  $form['#attributes'] = array('enctype' => 'multipart/form-data');

  return system_settings_form($form);
}

/**
 * Determine if a user is in compact mode.
 */
function system_admin_compact_mode() {
  global $user;
  return (isset($user->admin_compact_mode)) ? $user->admin_compact_mode : variable_get('admin_compact_mode', FALSE);
}

/**
 * This function formats an administrative page for viewing.
 *
 * @param $blocks
 *   An array of blocks to display. Each array should include a
 *   'title', a 'description', a formatted 'content' and a
 *   'position' which will control which container it will be
 *   in. This is usually 'left' or 'right'.
 * @themeable
 */
function theme_admin_page($blocks) {
  $stripe = 0;
  $container = array();

  foreach ($blocks as $block) {
    if ($block_output = theme('admin_block', $block)) {
      if (!$block['position']) {
        // perform automatic striping.
        $block['position'] = $stripe++ % 2 ? 'left' : 'right';
      }
      $container[$block['position']] .= $block_output;
    }
  }

  $output = '<div class="admin clear-block">';
  $output .= '<div class="compact-link">';
  if (system_admin_compact_mode()) {
    $output .= l(t('Show descriptions'), 'admin/compact/off', array('title' => t('Produce a less compact layout that includes descriptions.')));
  }
  else {
    $output .= l(t('Hide descriptions'), 'admin/compact/on', array('title' => t("Produce a more compact layout that doesn't include descriptions.")));
  }
  $output .= '</div>';

  foreach ($container as $id => $data) {
    $output .= '<div class="'. $id .' clear-block">';
    $output .= $data;
    $output .= '</div>';
  }
  $output .= '</div>';
  return $output;
}

/**
 * This function formats an administrative block for display.
 *
 * @param $block
 *   An array containing information about the block. It should
 *   include a 'title', a 'description' and a formatted 'content'.
 * @themeable
 */
function theme_admin_block($block) {
  // Don't display the block if it has no content to display.
  if (!$block['content']) {
    return '';
  }

  $output = <<< EOT
  <div class="admin-panel">
    <h3>
      $block[title]
    </h3>
    <div class="body">
      <p class="description">
        $block[description]
      </p>
      $block[content]
    </div>
  </div>
EOT;
  return $output;
}

/**
 * This function formats the content of an administrative block.
 *
 * @param $block
 *   An array containing information about the block. It should
 *   include a 'title', a 'description' and a formatted 'content'.
 * @themeable
 */
function theme_admin_block_content($content) {
  if (!$content) {
    return '';
  }

  if (system_admin_compact_mode()) {
    $output = '<ul class="menu">';
    foreach ($content as $item) {
      $output .= '<li class="leaf">'. l($item['title'], $item['path'], array('title' => $item['description'])) .'</li>';
    }
    $output .= '</ul>';
  }
  else {
    $output = '<dl class="admin-list">';
    foreach ($content as $item) {
      $output .= '<dt>'. l($item['title'], $item['path']) .'</dt>';
      $output .= '<dd>'. $item['description'] .'</dd>';
    }
    $output .= '</dl>';
  }
  return $output;
}

/**
 * Menu callback; prints a listing of admin tasks for each installed module.
 */
function _system_admin_by_module() {
  $modules = module_rebuild_cache();
  $menu_items = array();
  foreach ($modules as $file) {
    $module = $file->name;
    if ($module == 'help') {
      continue;
    }

    $admin_tasks = system_get_module_admin_tasks($module);

    // Only display a section if there are any available tasks.
    if (count($admin_tasks)) {

      // Check for help links.
      if (module_invoke($module, 'help', "admin/help#$module")) {
        $admin_tasks[100] = l(t('Get help'), "admin/help/$module");
      }

      // Sort.
      ksort($admin_tasks);

      $menu_items[$file->info['name']] = array($file->info['description'], $admin_tasks);
    }
  }
  return theme('system_admin_by_module', $menu_items);
}

function system_get_module_admin_tasks($module) {
  $admin_access = user_access('administer access control');
  $menu = menu_get_menu();
  $admin_tasks = array();

  // Check for permissions.
  if (module_hook($module, 'perm') && $admin_access) {
    $admin_tasks[-1] = l(t('Configure permissions'), 'admin/user/access', NULL, NULL, 'module-'. $module);
  }

  // Check for menu items that are admin links.
  if ($items = module_invoke($module, 'menu', TRUE)) {
    foreach ($items as $item) {
      $parts = explode('/', $item['path']);
      $n = count($parts);
      if ((!isset($item['type']) || ($item['type'] & MENU_VISIBLE_IN_TREE)) && ($parts[0] == 'admin') && ($n >= 3) && _menu_item_is_accessible($menu['path index'][$item['path']])) {
        $admin_tasks[$item['title']] = l($item['title'], $item['path']);
      }
    }
  }

  return $admin_tasks;
}


/**
 * Theme output of the dashboard page.
 */
function theme_system_admin_by_module($menu_items) {
  $stripe = 0;
  $output = '';
  $container = array();

  // Iterate over all modules
  foreach ($menu_items as $module => $block) {
    list($description, $items) = $block;

    // Output links
    if (count($items)) {
      $block = array();
      $block['title'] = $module;
      $block['content'] = theme('item_list', $items);
      $block['description'] = t($description);

      if ($block_output = theme('admin_block', $block)) {
        if (!$block['position']) {
          // Perform automatic striping.
          $block['position'] = ++$stripe % 2 ? 'left' : 'right';
        }
        $container[$block['position']] .= $block_output;
      }
    }
  }

  $output = '<div class="admin clear-block">';
  foreach ($container as $id => $data) {
    $output .= '<div class="'. $id .' clear-block">';
    $output .= $data;
    $output .= '</div>';
  }
  $output .= '</div>';

  return $output;
}
