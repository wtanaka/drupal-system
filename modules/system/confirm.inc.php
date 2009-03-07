<?php

/**
 * Output a confirmation form
 *
 * This function returns a complete form for confirming an action. A link is
 * offered to go back to the item that is being changed in case the user changes
 * his/her mind.
 *
 * If the submit handler for this form is invoked, the user successfully
 * confirmed the action. You should never directly inspect $_POST to see if an
 * action was confirmed.
 *
 * @ingroup forms
 * @param $form
 *   Additional elements to inject into the form, for example hidden elements.
 * @param $question
 *   The question to ask the user (e.g. "Are you sure you want to delete the
 *   block <em>foo</em>?").
 * @param $path
 *   The page to go to if the user denies the action.
 *   Can be either a drupal path, or an array with the keys 'path', 'query', 'fragment'.
 * @param $description
 *   Additional text to display (defaults to "This action cannot be undone.").
 * @param $yes
 *   A caption for the button which confirms the action (e.g. "Delete",
 *   "Replace", ...).
 * @param $no
 *   A caption for the link which denies the action (e.g. "Cancel").
 * @param $name
 *   The internal name used to refer to the confirmation item.
 * @return
 *   The form.
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
