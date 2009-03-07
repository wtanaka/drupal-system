<?php
/**
 * Implementation of hook_user().
 *
 * Allows users to individually set their theme and time zone.
 */
function _system_user($type, $edit, &$user, $category = NULL) {
  if ($type == 'form' && $category == 'account') {
    $form['theme_select'] = system_theme_select_form(t('Selecting a different theme will change the look and feel of the site.'), $edit['theme'], 2);

    if (variable_get('configurable_timezones', 1)) {
      $zones = _system_zonelist();
      $form['timezone'] = array(
        '#type'=>'fieldset',
        '#title' => t('Locale settings'),
        '#weight' => 6,
        '#collapsible' => TRUE,
      );
      $form['timezone']['timezone'] = array(
        '#type' => 'select',
        '#title' => t('Time zone'),
        '#default_value' => strlen($edit['timezone']) ? $edit['timezone'] : variable_get('date_default_timezone', 0),
        '#options' => $zones,
        '#description' => t('Select your current local time. Dates and times throughout this site will be displayed using this time zone.'),
      );
    }

    return $form;
  }
}
