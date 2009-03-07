<?php

/**
 * Implementation of hook_block().
 *
 * Generate a block with a promotional link to Drupal.org.
 */
function _real_system_block($op = 'list', $delta = 0, $edit = NULL) {
  switch ($op) {
    case 'list':
      $blocks[0] = array(
        'info' => t('Powered by Drupal'),
        'weight' => '10',
         // Not worth caching.
        'cache' => BLOCK_NO_CACHE,
      );
      return $blocks;
    case 'configure':
      // Compile a list of fields to show
      $form['wrapper']['color'] = array(
        '#type' => 'select',
        '#title' => t('Badge color'),
        '#default_value' => variable_get('drupal_badge_color', 'powered-blue'),
        '#options' => array('powered-black' => t('Black'), 'powered-blue' => t('Blue'), 'powered-gray' => t('Gray')),
      );
      $form['wrapper']['size'] = array(
        '#type' => 'select',
        '#title' => t('Badge size'),
        '#default_value' => variable_get('drupal_badge_size', '80x15'),
        '#options' => array('80x15' => t('Small'), '88x31' => t('Medium'), '135x42' => t('Large')),
      );
      return $form;
    case 'save':
      variable_set('drupal_badge_color', $edit['color']);
      variable_set('drupal_badge_size', $edit['size']);
      break;
    case 'view':
      $image_path = 'misc/'. variable_get('drupal_badge_color', 'powered-blue') .'-'. variable_get('drupal_badge_size', '80x15') .'.png';
      $block['subject'] = NULL; // Don't display a title
      $block['content'] = theme('system_powered_by', $image_path);
      return $block;
  }
}

/**
 * Format the Powered by Drupal text.
 *
 * @ingroup themeable
 */
function _real_theme_system_powered_by($image_path) {
  $image = theme('image', $image_path, t('Powered by Drupal, an open source content management system'), t('Powered by Drupal, an open source content management system'));
  return l($image, 'http://drupal.org', array('html' => TRUE, 'absolute' => TRUE, 'external' => TRUE));
}
