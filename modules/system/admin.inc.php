<?php
/**
 * Provide a single block on the administration overview page.
 *
 * @param $item
 *   The menu item to be displayed.
 */
function _real_system_admin_menu_block($item) {
  $content = array();
  if (!isset($item['mlid'])) {
    $item += db_fetch_array(db_query("SELECT mlid, menu_name FROM {menu_links} ml WHERE ml.router_path = '%s' AND module = 'system'", $item['path']));
  }
  $result = db_query("
    SELECT m.load_functions, m.to_arg_functions, m.access_callback, m.access_arguments, m.page_callback, m.page_arguments, m.title, m.title_callback, m.title_arguments, m.type, m.description, ml.*
    FROM {menu_links} ml
    LEFT JOIN {menu_router} m ON ml.router_path = m.path
    WHERE ml.plid = %d AND ml.menu_name = '%s' AND hidden = 0", $item['mlid'], $item['menu_name']);
  while ($item = db_fetch_array($result)) {
    _menu_link_translate($item);
    if (!$item['access']) {
      continue;
    }
    // The link 'description' either derived from the hook_menu 'description' or
    // entered by the user via menu module is saved as the title attribute.
    if (!empty($item['localized_options']['attributes']['title'])) {
      $item['description'] = $item['localized_options']['attributes']['title'];
    }
    // Prepare for sorting as in function _menu_tree_check_access().
    // The weight is offset so it is always positive, with a uniform 5-digits.
    $content[(50000 + $item['weight']) .' '. drupal_strtolower($item['title']) .' '. $item['mlid']] = $item;
  }
  ksort($content);
  return $content;
}

/**
 * Retrieves the current status of an array of files in the system table.
 *
 * @param $files
 *   An array of files to check.
 * @param $type
 *   The type of the files.
 */
function _real_system_get_files_database(&$files, $type) {
  // Extract current files from database.
  $result = db_query("SELECT filename, name, type, status, throttle, schema_version FROM {system} WHERE type = '%s'", $type);
  while ($file = db_fetch_object($result)) {
    if (isset($files[$file->name]) && is_object($files[$file->name])) {
      $file->old_filename = $file->filename;
      foreach ($file as $key => $value) {
        if (!isset($files[$file->name]) || !isset($files[$file->name]->$key)) {
          $files[$file->name]->$key = $value;
        }
      }
    }
  }
}

/**
 * Prepare defaults for themes.
 *
 * @return
 *   An array of default themes settings.
 */
function _real_system_theme_default() {
  return array(
    'regions' => array(
      'left' => 'Left sidebar',
      'right' => 'Right sidebar',
      'content' => 'Content',
      'header' => 'Header',
      'footer' => 'Footer',
    ),
    'description' => '',
    'features' => array(
      'comment_user_picture',
      'favicon',
      'mission',
      'logo',
      'name',
      'node_user_picture',
      'search',
      'slogan',
      'primary_links',
      'secondary_links',
    ),
    'stylesheets' => array(
      'all' => array('style.css')
    ),
    'scripts' => array('script.js'),
    'screenshot' => 'screenshot.png',
    'php' => DRUPAL_MINIMUM_PHP,
  );
}

/**
 * Collect data about all currently available themes.
 *
 * @return
 *   Array of all available themes and their data.
 */
function _real_system_theme_data() {
  $write_database = TRUE;
  // If lock not acquired, return $files data without writing to database.
  if (!lock_acquire('system_theme_data')) {
    $write_database = FALSE;
    // Wait for the parallel thread to be done so we are more likely
    // to get updated and consistent data.
    lock_wait('system_theme_data');
  }
  // Scan the installation theme .info files and their engines.
  $themes = _system_theme_data();
  foreach ($themes as $key => $theme) {
    if (!isset($theme->owner)) {
      $themes[$key]->owner = '';
    }
  }

  // Extract current files from database.
  system_get_files_database($themes, 'theme');

  // If lock not acquired, return $themes data without writing to database.
  if ($write_database) {
    $filenames = array();

    foreach ($themes as $theme) {
      // Record the filename of each theme that was found.
      $filenames[] = $theme->filename;
      // Existing themes will always have $theme->status set, since it's a
      // property that is only stored in the database.
      if (isset($theme->status)) {
        db_query("UPDATE {system} SET owner = '%s', info = '%s', filename = '%s' WHERE name = '%s' AND type = '%s'", $theme->owner, serialize($theme->info), $theme->filename, $theme->name, 'theme');
      }
      // New themes must get a $theme->status before they are inserted into the
      // database. For the default theme, we force it to be enabled (to handle
      // the initial installation of Drupal), but otherwise new themes should
      // always start off as disabled.
      else {
        $theme->status = ($theme->name == variable_get('theme_default', 'garland'));
        db_query("INSERT INTO {system} (name, owner, info, type, filename, status, throttle, bootstrap) VALUES ('%s', '%s', '%s', '%s', '%s', %d, %d, %d)", $theme->name, $theme->owner, serialize($theme->info), 'theme', $theme->filename, $theme->status, 0, 0);
      }
    }
    // Delete from the system table any themes missing from the file system.
    if ($filenames) {
      db_query("DELETE FROM {system} WHERE type = 'theme' AND filename NOT IN (". db_placeholders($filenames, 'varchar') .")", $filenames);
    }
    lock_release('system_theme_data');
  }
  return $themes;
}

/**
 * Helper function to scan and collect theme .info data and their engines.
 *
 * @return
 *   An associative array of themes information.
 */
function _real__system_theme_data() {
  static $themes_info = array();

  if (empty($themes_info)) {
    // Find themes
    $themes = drupal_system_listing('\.info$', 'themes');
    // Find theme engines
    $engines = drupal_system_listing('\.engine$', 'themes/engines');

    $defaults = system_theme_default();

    $sub_themes = array();
    // Read info files for each theme
    foreach ($themes as $key => $theme) {
      $themes[$key]->info = drupal_parse_info_file($theme->filename) + $defaults;

      // Invoke hook_system_info_alter() to give installed modules a chance to
      // modify the data in the .info files if necessary.
      drupal_alter('system_info', $themes[$key]->info, $themes[$key]);

      if (!empty($themes[$key]->info['base theme'])) {
        $sub_themes[] = $key;
      }
      if (empty($themes[$key]->info['engine'])) {
        $filename = dirname($themes[$key]->filename) .'/'. $themes[$key]->name .'.theme';
        if (file_exists($filename)) {
          $themes[$key]->owner = $filename;
          $themes[$key]->prefix = $key;
        }
      }
      else {
        $engine = $themes[$key]->info['engine'];
        if (isset($engines[$engine])) {
          $themes[$key]->owner = $engines[$engine]->filename;
          $themes[$key]->prefix = $engines[$engine]->name;
          $themes[$key]->template = TRUE;
        }
      }

      // Give the stylesheets proper path information.
      $pathed_stylesheets = array();
      foreach ($themes[$key]->info['stylesheets'] as $media => $stylesheets) {
        foreach ($stylesheets as $stylesheet) {
          $pathed_stylesheets[$media][$stylesheet] = dirname($themes[$key]->filename) .'/'. $stylesheet;
        }
      }
      $themes[$key]->info['stylesheets'] = $pathed_stylesheets;

      // Give the scripts proper path information.
      $scripts = array();
      foreach ($themes[$key]->info['scripts'] as $script) {
        $scripts[$script] = dirname($themes[$key]->filename) .'/'. $script;
      }
      $themes[$key]->info['scripts'] = $scripts;
      // Give the screenshot proper path information.
      if (!empty($themes[$key]->info['screenshot'])) {
        $themes[$key]->info['screenshot'] = dirname($themes[$key]->filename) .'/'. $themes[$key]->info['screenshot'];
      }
    }

    // Now that we've established all our master themes, go back and fill in
    // data for subthemes.
    foreach ($sub_themes as $key) {
      $themes[$key]->base_themes = system_find_base_themes($themes, $key);
      // Don't proceed if there was a problem with the root base theme.
      if (!current($themes[$key]->base_themes)) {
        continue;
      }
      $base_key = key($themes[$key]->base_themes);
      foreach (array_keys($themes[$key]->base_themes) as $base_theme) {
        $themes[$base_theme]->sub_themes[$key] = $themes[$key]->info['name'];
      }
      // Copy the 'owner' and 'engine' over if the top level theme uses a
      // theme engine.
      if (isset($themes[$base_key]->owner)) {
        if (isset($themes[$base_key]->info['engine'])) {
          $themes[$key]->info['engine'] = $themes[$base_key]->info['engine'];
          $themes[$key]->owner = $themes[$base_key]->owner;
          $themes[$key]->prefix = $themes[$base_key]->prefix;
        }
        else {
          $themes[$key]->prefix = $key;
        }
      }
    }

    $themes_info = $themes;
  }

  // To avoid side effects, we don't return the original objects.
  $themes_info_cloned = array();
  foreach ($themes_info as $key => $theme) {
    $themes_info_cloned[$key] = drupal_clone($theme);
  }

  return $themes_info_cloned;
}

/**
 * Find all the base themes for the specified theme.
 *
 * Themes can inherit templates and function implementations from earlier themes.
 *
 * @param $themes
 *   An array of available themes.
 * @param $key
 *   The name of the theme whose base we are looking for.
 * @param $used_keys
 *   A recursion parameter preventing endless loops.
 * @return
 *   Returns an array of all of the theme's ancestors; the first element's value
 *   will be NULL if an error occurred.
 */
function _real_system_find_base_themes($themes, $key, $used_keys = array()) {
  $base_key = $themes[$key]->info['base theme'];
  // Does the base theme exist?
  if (!isset($themes[$base_key])) {
    return array($base_key => NULL);
  }

  $current_base_theme = array($base_key => $themes[$base_key]->info['name']);

  // Is the base theme itself a child of another theme?
  if (isset($themes[$base_key]->info['base theme'])) {
    // Do we already know the base themes of this theme?
    if (isset($themes[$base_key]->base_themes)) {
      return $themes[$base_key]->base_themes + $current_base_theme;
    }
    // Prevent loops.
    if (!empty($used_keys[$base_key])) {
      return array($base_key => NULL);
    }
    $used_keys[$base_key] = TRUE;
    return system_find_base_themes($themes, $base_key, $used_keys) + $current_base_theme;
  }
  // If we get here, then this is our parent theme.
  return $current_base_theme;
}

/**
 * This function has been deprecated in favor of system_find_base_themes().
 *
 * Recursive function to find the top level base theme. Themes can inherit
 * templates and function implementations from earlier themes.
 *
 * @param $themes
 *   An array of available themes.
 * @param $key
 *   The name of the theme whose base we are looking for.
 * @param $used_keys
 *   A recursion parameter preventing endless loops.
 * @return
 *   Returns the top level parent that has no ancestor or returns NULL if there isn't a valid parent.
 */
function _real_system_find_base_theme($themes, $key, $used_keys = array()) {
  $base_key = $themes[$key]->info['base theme'];
  // Does the base theme exist?
  if (!isset($themes[$base_key])) {
    return NULL;
  }

  // Is the base theme itself a child of another theme?
  if (isset($themes[$base_key]->info['base theme'])) {
    // Prevent loops.
    if (!empty($used_keys[$base_key])) {
      return NULL;
    }
    $used_keys[$base_key] = TRUE;
    return system_find_base_theme($themes, $base_key, $used_keys);
  }
  // If we get here, then this is our parent theme.
  return $base_key;
}

/**
 * Generate a list of tasks offered by a specified module.
 *
 * @param $module
 *   Module name.
 * @return
 *   An array of task links.
 */
function _real_system_get_module_admin_tasks($module) {
  static $items;

  $admin_access = user_access('administer permissions');
  $admin_tasks = array();

  if (!isset($items)) {
    $result = db_query("
       SELECT m.load_functions, m.to_arg_functions, m.access_callback, m.access_arguments, m.page_callback, m.page_arguments, m.title, m.title_callback, m.title_arguments, m.type, ml.*
       FROM {menu_links} ml INNER JOIN {menu_router} m ON ml.router_path = m.path WHERE ml.link_path LIKE 'admin/%' AND hidden >= 0 AND module = 'system' AND m.number_parts > 2");
    $items = array();
    while ($item = db_fetch_array($result)) {
      _menu_link_translate($item);
      if ($item['access']) {
        $items[$item['router_path']] = $item;
      }
    }
  }
  $admin_tasks = array();
  $admin_task_count = 0;
  // Check for permissions.
  if (module_hook($module, 'perm') && $admin_access) {
    $admin_tasks[-1] = l(t('Configure permissions'), 'admin/user/permissions', array('fragment' => 'module-'. $module));
  }

  // Check for menu items that are admin links.
  if ($menu = module_invoke($module, 'menu')) {
    foreach (array_keys($menu) as $path) {
      if (isset($items[$path])) {
        $admin_tasks[$items[$path]['title'] . $admin_task_count ++] = l($items[$path]['title'], $path);
      }
    }
  }

  return $admin_tasks;
}
