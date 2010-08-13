<?php

/**
 * Menu callback. Display an overview of available and configured actions.
 */
function _real_system_actions_manage() {
  $output = '';
  $actions = actions_list();
  actions_synchronize($actions);
  $actions_map = actions_actions_map($actions);
  $options = array(t('Choose an advanced action'));
  $unconfigurable = array();

  foreach ($actions_map as $key => $array) {
    if ($array['configurable']) {
      $options[$key] = $array['description'] .'...';
    }
    else {
      $unconfigurable[] = $array;
    }
  }

  $row = array();
  $instances_present = db_fetch_object(db_query("SELECT aid FROM {actions} WHERE parameters <> ''"));
  $header = array(
    array('data' => t('Action type'), 'field' => 'type'),
    array('data' => t('Description'), 'field' => 'description'),
    array('data' => $instances_present ? t('Operations') : '', 'colspan' => '2')
  );
  $sql = 'SELECT * FROM {actions}';
  $result = pager_query($sql . tablesort_sql($header), 50);
  while ($action = db_fetch_object($result)) {
    $row[] = array(
      array('data' => $action->type),
      array('data' => filter_xss_admin($action->description)),
      array('data' => $action->parameters ? l(t('configure'), "admin/settings/actions/configure/$action->aid") : ''),
      array('data' => $action->parameters ? l(t('delete'), "admin/settings/actions/delete/$action->aid") : '')
    );
  }

  if ($row) {
    $pager = theme('pager', NULL, 50, 0);
    if (!empty($pager)) {
      $row[] = array(array('data' => $pager, 'colspan' => '3'));
    }
    $output .= '<h3>'. t('Actions available to Drupal:') .'</h3>';
    $output .= theme('table', $header, $row);
  }

  if ($actions_map) {
    $output .= drupal_get_form('system_actions_manage_form', $options);
  }

  return $output;
}

/**
 * Define the form for the actions overview page.
 *
 * @see system_actions_manage_form_submit()
 * @ingroup forms
 * @param $form_state
 *   An associative array containing the current state of the form; not used.
 * @param $options
 *   An array of configurable actions.
 * @return
 *   Form definition.
 */
function _real_system_actions_manage_form($form_state, $options = array()) {
  $form['parent'] = array(
    '#type' => 'fieldset',
    '#title' => t('Make a new advanced action available'),
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
  );
  $form['parent']['action'] = array(
    '#type' => 'select',
    '#default_value' => '',
    '#options' => $options,
    '#description' => '',
  );
  $form['parent']['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Create'),
  );
  return $form;
}

/**
 * Process system_actions_manage form submissions.
 */
function _real_system_actions_manage_form_submit($form, &$form_state) {
  if ($form_state['values']['action']) {
    $form_state['redirect'] = 'admin/settings/actions/configure/'. $form_state['values']['action'];
  }
}

/**
 * Menu callback. Create the form for configuration of a single action.
 *
 * We provide the "Description" field. The rest of the form
 * is provided by the action. We then provide the Save button.
 * Because we are combining unknown form elements with the action
 * configuration form, we use actions_ prefix on our elements.
 *
 * @see system_actions_configure_validate()
 * @see system_actions_configure_submit()
 * @param $action
 *   md5 hash of action ID or an integer. If it's an md5 hash, we
 *   are creating a new instance. If it's an integer, we're editing
 *   an existing instance.
 * @return
 *   Form definition.
 */
function _real_system_actions_configure($form_state, $action = NULL) {
  if ($action === NULL) {
    drupal_goto('admin/settings/actions');
  }

  $actions_map = actions_actions_map(actions_list());
  $edit = array();

  // Numeric action denotes saved instance of a configurable action;
  // else we are creating a new action instance.
  if (is_numeric($action)) {
    $aid = $action;
    // Load stored parameter values from database.
    $data = db_fetch_object(db_query("SELECT * FROM {actions} WHERE aid = '%s'", $aid));
    $edit['actions_description'] = $data->description;
    $edit['actions_type'] = $data->type;
    $function = $data->callback;
    $action = md5($data->callback);
    $params = unserialize($data->parameters);
    if ($params) {
      foreach ($params as $name => $val) {
        $edit[$name] = $val;
      }
    }
  }
  else {
    $function = $actions_map[$action]['callback'];
    $edit['actions_description'] = $actions_map[$action]['description'];
    $edit['actions_type'] = $actions_map[$action]['type'];
  }

  $form['actions_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#default_value' => $edit['actions_description'],
    '#maxlength' => '255',
    '#description' => t('A unique description for this advanced action. This description will be displayed in the interface of modules that integrate with actions, such as Trigger module.'),
    '#weight' => -10
  );
  $action_form = $function .'_form';
  $form = array_merge($form, $action_form($edit));
  $form['actions_type'] = array(
    '#type' => 'value',
    '#value' => $edit['actions_type'],
  );
  $form['actions_action'] = array(
    '#type' => 'hidden',
    '#value' => $action,
  );
  // $aid is set when configuring an existing action instance.
  if (isset($aid)) {
    $form['actions_aid'] = array(
      '#type' => 'hidden',
      '#value' => $aid,
    );
  }
  $form['actions_configured'] = array(
    '#type' => 'hidden',
    '#value' => '1',
  );
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 13
  );

  return $form;
}

/**
 * Validate system_actions_configure form submissions.
 */
function _real_system_actions_configure_validate($form, $form_state) {
  $function = actions_function_lookup($form_state['values']['actions_action']) .'_validate';
  // Hand off validation to the action.
  if (function_exists($function)) {
    $function($form, $form_state);
  }
}

/**
 * Process system_actions_configure form submissions.
 */
function _real_system_actions_configure_submit($form, &$form_state) {
  $function = actions_function_lookup($form_state['values']['actions_action']);
  $submit_function = $function .'_submit';

  // Action will return keyed array of values to store.
  $params = $submit_function($form, $form_state);
  $aid = isset($form_state['values']['actions_aid']) ? $form_state['values']['actions_aid'] : NULL;

  actions_save($function, $form_state['values']['actions_type'], $params, $form_state['values']['actions_description'], $aid);
  drupal_set_message(t('The action has been successfully saved.'));

  $form_state['redirect'] = 'admin/settings/actions/manage';
}

/**
 * Create the form for confirmation of deleting an action.
 *
 * @ingroup forms
 * @see system_actions_delete_form_submit()
 */
function _real_system_actions_delete_form($form_state, $action) {

  $form['aid'] = array(
    '#type' => 'hidden',
    '#value' => $action->aid,
  );
  return confirm_form($form,
    t('Are you sure you want to delete the action %action?', array('%action' => $action->description)),
    'admin/settings/actions/manage',
    t('This cannot be undone.'),
    t('Delete'), t('Cancel')
  );
}

/**
 * Process system_actions_delete form submissions.
 *
 * Post-deletion operations for action deletion.
 */
function _real_system_actions_delete_form_submit($form, &$form_state) {
  $aid = $form_state['values']['aid'];
  $action = actions_load($aid);
  actions_delete($aid);
  watchdog('user', 'Deleted action %aid (%action)', array('%aid' => $aid, '%action' => $action->description));
  drupal_set_message(t('Action %action was deleted', array('%action' => $action->description)));
  $form_state['redirect'] = 'admin/settings/actions/manage';
}

/**
 * Post-deletion operations for deleting action orphans.
 *
 * @param $orphaned
 *   An array of orphaned actions.
 */
function _real_system_action_delete_orphans_post($orphaned) {
  foreach ($orphaned as $callback) {
    drupal_set_message(t("Deleted orphaned action (%action).", array('%action' => $callback)));
  }
}

/**
 * Remove actions that are in the database but not supported by any enabled module.
 */
function _real_system_actions_remove_orphans() {
  actions_synchronize(actions_list(), TRUE);
  drupal_goto('admin/settings/actions/manage');
}

/**
 * Return a form definition so the Send email action can be configured.
 *
 * @see system_send_email_action_validate()
 * @see system_send_email_action_submit()
 * @param $context
 *   Default values (if we are editing an existing action instance).
 * @return
 *   Form definition.
 */
function _real_system_send_email_action_form($context) {
  // Set default values for form.
  if (!isset($context['recipient'])) {
    $context['recipient'] = '';
  }
  if (!isset($context['subject'])) {
    $context['subject'] = '';
  }
  if (!isset($context['message'])) {
    $context['message'] = '';
  }

  $form['recipient'] = array(
    '#type' => 'textfield',
    '#title' => t('Recipient'),
    '#default_value' => $context['recipient'],
    '#maxlength' => '254',
    '#description' => t('The email address to which the message should be sent OR enter %author if you would like to send an e-mail to the author of the original post.', array('%author' => '%author')),
  );
  $form['subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => $context['subject'],
    '#maxlength' => '254',
    '#description' => t('The subject of the message.'),
  );
  $form['message'] = array(
    '#type' => 'textarea',
    '#title' => t('Message'),
    '#default_value' => $context['message'],
    '#cols' => '80',
    '#rows' => '20',
    '#description' => t('The message that should be sent. You may include the following variables: %site_name, %username, %node_url, %node_type, %title, %teaser, %body. Not all variables will be available in all contexts.'),
  );
  return $form;
}

/**
 * Validate system_send_email_action form submissions.
 */
function _real_system_send_email_action_validate($form, $form_state) {
  $form_values = $form_state['values'];
  // Validate the configuration form.
  if (!valid_email_address($form_values['recipient']) && $form_values['recipient'] != '%author') {
    // We want the literal %author placeholder to be emphasized in the error message.
    form_set_error('recipient', t('Please enter a valid email address or %author.', array('%author' => '%author')));
  }
}

/**
 * Process system_send_email_action form submissions.
 */
function _real_system_send_email_action_submit($form, $form_state) {
  $form_values = $form_state['values'];
  // Process the HTML form to store configuration. The keyed array that
  // we return will be serialized to the database.
  $params = array(
    'recipient' => $form_values['recipient'],
    'subject'   => $form_values['subject'],
    'message'   => $form_values['message'],
  );
  return $params;
}

/**
 * Implementation of a configurable Drupal action. Sends an email.
 */
function _real_system_send_email_action($object, $context) {
  global $user;

  switch ($context['hook']) {
    case 'nodeapi':
      // Because this is not an action of type 'node' the node
      // will not be passed as $object, but it will still be available
      // in $context.
      $node = $context['node'];
      break;
    // The comment hook provides nid, in $context.
    case 'comment':
      $comment = $context['comment'];
      $node = node_load($comment->nid);
      break;
    case 'user':
      // Because this is not an action of type 'user' the user
      // object is not passed as $object, but it will still be available
      // in $context.
      $account = $context['account'];
      if (isset($context['node'])) {
        $node = $context['node'];
      }
      elseif ($context['recipient'] == '%author') {
        // If we don't have a node, we don't have a node author.
        watchdog('error', 'Cannot use %author token in this context.');
        return;
      }
      break;
    default:
      // We are being called directly.
      $node = $object;
  }

  $recipient = $context['recipient'];

  if (isset($node)) {
    if (!isset($account)) {
      $account = user_load(array('uid' => $node->uid));
    }
    if ($recipient == '%author') {
      $recipient = $account->mail;
    }
  }

  if (!isset($account)) {
    $account = $user;

  }
  $language = user_preferred_language($account);
  $params = array('account' => $account, 'object' => $object, 'context' => $context);
  if (isset($node)) {
    $params['node'] = $node;
  }

  if (drupal_mail('system', 'action_send_email', $recipient, $language, $params)) {
    watchdog('action', 'Sent email to %recipient', array('%recipient' => $recipient));
  }
  else {
    watchdog('error', 'Unable to send email to %recipient', array('%recipient' => $recipient));
  }
}

function _real_system_message_action_form($context) {
  $form['message'] = array(
    '#type' => 'textarea',
    '#title' => t('Message'),
    '#default_value' => isset($context['message']) ? $context['message'] : '',
    '#required' => TRUE,
    '#rows' => '8',
    '#description' => t('The message to be displayed to the current user. You may include the following variables: %site_name, %username, %node_url, %node_type, %title, %teaser, %body. Not all variables will be available in all contexts.'),
  );
  return $form;
}

function _real_system_message_action_submit($form, $form_state) {
  return array('message' => $form_state['values']['message']);
}

/**
 * A configurable Drupal action. Sends a message to the current user's screen.
 */
function _real_system_message_action(&$object, $context = array()) {
  global $user;
  $variables = array(
    '%site_name' => variable_get('site_name', 'Drupal'),
    '%username' => $user->name ? $user->name : variable_get('anonymous', t('Anonymous')),
  );

  // This action can be called in any context, but if placeholders
  // are used a node object must be present to be the source
  // of substituted text.
  switch ($context['hook']) {
    case 'nodeapi':
      // Because this is not an action of type 'node' the node
      // will not be passed as $object, but it will still be available
      // in $context.
      $node = $context['node'];
      break;
    // The comment hook also provides the node, in context.
    case 'comment':
      $comment = $context['comment'];
      $node = node_load($comment->nid);
      break;
    case 'taxonomy':
      $vocabulary = taxonomy_vocabulary_load($object->vid);
      $variables = array_merge($variables, array(
        '%term_name' => check_plain($object->name),
        '%term_description' => filter_xss_admin($object->description),
        '%term_id' => $object->tid,
        '%vocabulary_name' => check_plain($vocabulary->name),
        '%vocabulary_description' => filter_xss_admin($vocabulary->description),
        '%vocabulary_id' => $vocabulary->vid,
        )
      );
      break;
    default:
      // We are being called directly.
      $node = $object;
  }

  if (isset($node) && is_object($node)) {
    $variables = array_merge($variables, array(
      '%uid' => $node->uid,
      '%node_url' => url('node/'. $node->nid, array('absolute' => TRUE)),
      '%node_type' => check_plain(node_get_types('name', $node)),
      '%title' => check_plain($node->title),
      '%teaser' => check_markup($node->teaser, $node->format, FALSE),
      '%body' => check_markup($node->body, $node->format, FALSE),
      )
    );
  }
  $context['message'] = strtr(filter_xss_admin($context['message']), $variables);
  drupal_set_message($context['message']);
}

/**
 * Implementation of a configurable Drupal action. Redirect user to a URL.
 */
function _real_system_goto_action_form($context) {
  $form['url'] = array(
    '#type' => 'textfield',
    '#title' => t('URL'),
    '#description' => t('The URL to which the user should be redirected. This can be an internal URL like node/1234 or an external URL like http://drupal.org.'),
    '#default_value' => isset($context['url']) ? $context['url'] : '',
    '#required' => TRUE,
  );
  return $form;
}

function _real_system_goto_action_submit($form, $form_state) {
  return array(
    'url' => $form_state['values']['url']
  );
}

function _real_system_goto_action($object, $context) {
  drupal_goto($context['url']);
}
