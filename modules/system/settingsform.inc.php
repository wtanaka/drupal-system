<?php

/**
 * Add default buttons to a form and set its prefix.
 *
 * @ingroup forms
 * @see system_settings_form_submit()
 * @param $form
 *   An associative array containing the structure of the form.
 * @return
 *   The form structure.
 */
function _real_system_settings_form($form) {
  $form['buttons']['submit'] = array('#type' => 'submit', '#value' => t('Save configuration') );
  $form['buttons']['reset'] = array('#type' => 'submit', '#value' => t('Reset to defaults') );

  if (!empty($_POST) && form_get_errors()) {
    drupal_set_message(t('The settings have not been saved because of the errors.'), 'error');
  }
  $form['#submit'][] = 'system_settings_form_submit';
  $form['#theme'] = 'system_settings_form';
  return $form;
}

/**
 * Execute the system_settings_form.
 *
 * If you want node type configure style handling of your checkboxes,
 * add an array_filter value to your form.
 */
function _real_system_settings_form_submit($form, &$form_state) {
  $op = isset($form_state['values']['op']) ? $form_state['values']['op'] : '';

  // Exclude unnecessary elements.
  unset($form_state['values']['submit'], $form_state['values']['reset'], $form_state['values']['form_id'], $form_state['values']['op'], $form_state['values']['form_token'], $form_state['values']['form_build_id']);

  foreach ($form_state['values'] as $key => $value) {
    if ($op == t('Reset to defaults')) {
      variable_del($key);
    }
    else {
      if (is_array($value) && isset($form_state['values']['array_filter'])) {
        $value = array_keys(array_filter($value));
      }
      variable_set($key, $value);
    }
  }
  if ($op == t('Reset to defaults')) {
    drupal_set_message(t('The configuration options have been reset to their default values.'));
  }
  else {
    drupal_set_message(t('The configuration options have been saved.'));
  }

  cache_clear_all();
  drupal_rebuild_theme_registry();
}
