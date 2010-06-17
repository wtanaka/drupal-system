<?php

/**
 * Generates a form array for a confirmation form.
 *
 * This function returns a complete form array for confirming an action. The
 * form contains a confirm button as well as a cancellation link that allows a
 * user to abort the action.
 *
 * If the submit handler for a form that implements confirm_form() is invoked,
 * the user successfully confirmed the action. You should never directly
 * inspect $_POST to see if an action was confirmed.
 *
 * Note - if the parameters $question, $description, $yes, or $no could contain
 * any user input (such as node titles or taxonomy terms), it is the
 * responsibility of the code calling confirm_form() to sanitize them first with
 * a function like check_plain() or filter_xss().
 *
 * @param $form
 *   Additional elements to add to the form; for example, hidden elements.
 * @param $question
 *   The question to ask the user (e.g. "Are you sure you want to delete the
 *   block <em>foo</em>?"). The page title will be set to this value.
 * @param $path
 *   The page to go to if the user cancels the action. This can be either:
 *   - A string containing a Drupal path.
 *   - An associative array with a 'path' key. Additional array values are
 *     passed as the $options parameter to l().
 *   If the 'destination' query parameter is set in the URL when viewing a
 *   confirmation form, that value will be used instead of $path.
 * @param $description
 *   Additional text to display. Defaults to t('This action cannot be undone.').
 * @param $yes
 *   A caption for the button that confirms the action (e.g. "Delete",
 *   "Replace", ...). Defaults to t('Confirm').
 * @param $no
 *   A caption for the link which cancels the action (e.g. "Cancel"). Defaults
 *   to t('Cancel').
 * @param $name
 *   The internal name used to refer to the confirmation item.
 *
 * @return
 *   The form array.
 */
function _real_confirm_form($form, $question, $path, $description = NULL, $yes = NULL, $no = NULL, $name = 'confirm') {
  $description = isset($description) ? $description : t('This action cannot be undone.');

  // Prepare cancel link
  $query = $fragment = NULL;
  if (is_array($path)) {
    $query = isset($path['query']) ? $path['query'] : NULL;
    $fragment = isset($path['fragment']) ? $path['fragment'] : NULL;
    $path = isset($path['path']) ? $path['path'] : NULL;
  }
  $cancel = l($no ? $no : t('Cancel'), $path, array('query' => $query, 'fragment' => $fragment));

  drupal_set_title($question);

  // Confirm form fails duplication check, as the form values rarely change -- so skip it.
  $form['#skip_duplicate_check'] = TRUE;

  $form['#attributes'] = array('class' => 'confirmation');
  $form['description'] = array('#value' => $description);
  $form[$name] = array('#type' => 'hidden', '#value' => 1);

  $form['actions'] = array('#prefix' => '<div class="container-inline">', '#suffix' => '</div>');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => $yes ? $yes : t('Confirm'));
  $form['actions']['cancel'] = array('#value' => $cancel);
  $form['#theme'] = 'confirm_form';
  return $form;
}
