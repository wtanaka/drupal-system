<?php

/**
 * Process admin theme form submissions.
 */
function _real_system_admin_theme_submit($form, &$form_state) {
  // If we're changing themes, make sure the theme has its blocks initialized.
  if ($form_state['values']['admin_theme'] && $form_state['values']['admin_theme'] != variable_get('admin_theme', '0')) {
    $result = db_result(db_query("SELECT COUNT(*) FROM {blocks} WHERE theme = '%s'", $form_state['values']['admin_theme']));
    if (!$result) {
      system_initialize_theme_blocks($form_state['values']['admin_theme']);
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
function _real_system_theme_select_form($description = '', $default_value = '', $weight = 0) {
  if (user_access('select different theme')) {
    $enabled = array();
    $themes = list_themes();

    foreach ($themes as $theme) {
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

        $screenshot = NULL;
        $theme_key = $info->name;
        while ($theme_key) {
          if (file_exists($themes[$theme_key]->info['screenshot'])) {
            $screenshot = $themes[$theme_key]->info['screenshot'];
            break;
          }
          $theme_key = isset($themes[$theme_key]->info['base theme']) ? $themes[$theme_key]->info['base theme'] : NULL;
        }

        $screenshot = $screenshot ? theme('image', $screenshot, t('Screenshot for %theme theme', array('%theme' => $info->name)), '', array('class' => 'screenshot'), FALSE) : t('no screenshot');

        $form['themes'][$info->key]['screenshot'] = array('#value' => $screenshot);
        $form['themes'][$info->key]['description'] = array('#type' => 'item', '#title' => $info->name, '#value' => dirname($info->filename) . ($info->name == variable_get('theme_default', 'garland') ? '<br /> <em>'. t('(site default theme)') .'</em>' : ''));
        $options[$info->key] = '';
      }

      $form['themes']['theme'] = array('#type' => 'radios', '#options' => $options, '#default_value' => $default_value ? $default_value : '');
      $form['#weight'] = $weight;
      return $form;
    }
  }
}

/**
 * Checks the existence of the directory specified in $form_element. This
 * function _real_is called from the system_settings form to check both the
 * file_directory_path and file_directory_temp directories. If validation
 * fails, the form element is flagged with an error from within the
 * file_check_directory function.
 *
 * @param $form_element
 *   The form element containing the name of the directory to check.
 */
function _real_system_check_directory($form_element) {
  file_check_directory($form_element['#value'], FILE_CREATE_DIRECTORY, $form_element['#parents'][0]);
  return $form_element;
}

/**
 * Assign an initial, default set of blocks for a theme.
 *
 * This function is called the first time a new theme is enabled. The new theme
 * gets a copy of the default theme's blocks, with the difference that if a
 * particular region isn't available in the new theme, the block is assigned
 * to the new theme's default region.
 *
 * @param $theme
 *   The name of a theme.
 */
function _real_system_initialize_theme_blocks($theme) {
  // Initialize theme's blocks if none already registered.
  if (!(db_result(db_query("SELECT COUNT(*) FROM {blocks} WHERE theme = '%s'", $theme)))) {
    $default_theme = variable_get('theme_default', 'garland');
    $regions = system_region_list($theme);
    $result = db_query("SELECT * FROM {blocks} WHERE theme = '%s'", $default_theme);
    while ($block = db_fetch_array($result)) {
      // If the region isn't supported by the theme, assign the block to the theme's default region.
      if (!array_key_exists($block['region'], $regions)) {
        $block['region'] = system_default_region($theme);
      }
      db_query("INSERT INTO {blocks} (module, delta, theme, status, weight, region, visibility, pages, custom, throttle, cache) VALUES ('%s', '%s', '%s', %d, %d, '%s', %d, '%s', %d, %d, %d)",
          $block['module'], $block['delta'], $theme, $block['status'], $block['weight'], $block['region'], $block['visibility'], $block['pages'], $block['custom'], $block['throttle'], $block['cache']);
    }
  }
}

/**
 * Helper function to sort requirements.
 */
function _real_system_sort_requirements($a, $b) {
  if (!isset($a['weight'])) {
    if (!isset($b['weight'])) {
      return strcmp($a['title'], $b['title']);
    }
    return -$b['weight'];
  }
  return isset($b['weight']) ? $a['weight'] - $b['weight'] : $a['weight'];
}

/**
 * Implementation of hook_node_type().
 *
 * Updates theme settings after a node type change.
 */
function _real_system_node_type($op, $info) {
  if ($op == 'update' && !empty($info->old_type) && $info->type != $info->old_type) {
    $old = 'toggle_node_info_'. $info->old_type;
    $new = 'toggle_node_info_'. $info->type;

    $theme_settings = variable_get('theme_settings', array());
    if (isset($theme_settings[$old])) {
      $theme_settings[$new] = $theme_settings[$old];
      unset($theme_settings[$old]);
      variable_set('theme_settings', $theme_settings);
    }
  }
}

/**
 * Implementation of hook_action_info().
 */
function _real_system_action_info() {
  return array(
    'system_message_action' => array(
      'type' => 'system',
      'description' => t('Display a message to the user'),
      'configurable' => TRUE,
      'hooks' => array(
        'nodeapi' => array('view', 'insert', 'update', 'delete'),
        'comment' => array('view', 'insert', 'update', 'delete'),
        'user' => array('view', 'insert', 'update', 'delete', 'login'),
        'taxonomy' => array('insert', 'update', 'delete'),
      ),
    ),
    'system_send_email_action' => array(
      'description' => t('Send e-mail'),
      'type' => 'system',
      'configurable' => TRUE,
      'hooks' => array(
        'nodeapi' => array('view', 'insert', 'update', 'delete'),
        'comment' => array('view', 'insert', 'update', 'delete'),
        'user' => array('view', 'insert', 'update', 'delete', 'login'),
        'taxonomy' => array('insert', 'update', 'delete'),
      )
    ),
    'system_goto_action' => array(
      'description' => t('Redirect to URL'),
      'type' => 'system',
      'configurable' => TRUE,
      'hooks' => array(
        'nodeapi' => array('view', 'insert', 'update', 'delete'),
        'comment' => array('view', 'insert', 'update', 'delete'),
        'user' => array('view', 'insert', 'update', 'delete', 'login'),
      )
    )
  );
}


/**
 * Generate an array of time zones and their local time&date.
 */
function _real_system_zonelist() {
  $timestamp = time();
  $zonelist = array(-11, -10, -9.5, -9, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 0, 1, 2, 3, 3.5, 4, 5, 5.5, 5.75, 6, 6.5, 7, 8, 9, 9.5, 10, 10.5, 11, 11.5, 12, 12.75, 13, 14);
  $zones = array();
  foreach ($zonelist as $offset) {
    $zone = $offset * 3600;
    $zones[$zone] = format_date($timestamp, 'custom', variable_get('date_format_long', 'l, F j, Y - H:i') .' O', $zone);
  }
  return $zones;
}

/**
 * Checks whether the server is capable of issuing HTTP requests.
 *
 * The function sets the drupal_http_request_fail system variable to TRUE if
 * drupal_http_request() does not work and then the system status report page
 * will contain an error.
 *
 * @return
 *  TRUE if this installation can issue HTTP requests.
 */
function _real_system_check_http_request() {
  // Try to get the content of the front page via drupal_http_request().
  $result = drupal_http_request(url('', array('absolute' => TRUE)));
  // We only care that we get a http response - this means that Drupal
  // can make a http request.
  $works = isset($result->code) && ($result->code >= 100) && ($result->code < 600);
  variable_set('drupal_http_request_fails', !$works);
  return $works;
}
