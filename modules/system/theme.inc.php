<?php
function _real_system_theme() {
  return array_merge(drupal_common_theme(), array(
    'system_theme_select_form' => array(
      'arguments' => array('form' => NULL),
      'file' => 'system.admin.inc',
    ),
    'system_themes_form' => array(
      'arguments' => array('form' => NULL),
      'file' => 'system.admin.inc',
    ),
    'system_modules' => array(
      'arguments' => array('form' => NULL),
      'file' => 'system.admin.inc',
    ),
    'system_modules_uninstall' => array(
      'arguments' => array('form' => NULL),
      'file' => 'system.admin.inc',
    ),
    'status_report' => array(
      'arguments' => array('requirements' => NULL),
      'file' => 'system.admin.inc',
    ),
    'admin_page' => array(
      'arguments' => array('blocks' => NULL),
      'file' => 'system.admin.inc',
    ),
    'admin_block' => array(
      'arguments' => array('block' => NULL),
      'file' => 'system.admin.inc',
    ),
    'admin_block_content' => array(
      'arguments' => array('content' => NULL),
      'file' => 'system.admin.inc',
    ),
    'system_admin_by_module' => array(
      'arguments' => array('menu_items' => NULL),
      'file' => 'system.admin.inc',
    ),
    'system_powered_by' => array(
      'arguments' => array('image_path' => NULL),
    ),
  ));
}
