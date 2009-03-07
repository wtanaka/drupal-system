<?php
function _real_system_help($path, $arg) {
  global $base_url;

  switch ($path) {
    case 'admin/help#system':
      $output = '<p>'. t('The system module is at the foundation of your Drupal website, and provides basic but extensible functionality for use by other modules and themes. Some integral elements of Drupal are contained in and managed by the system module, including caching, enabling or disabling of modules and themes, preparing and displaying the administrative page, and configuring fundamental site settings. A number of key system maintenance operations are also part of the system module.') .'</p>';
      $output .= '<p>'. t('The system module provides:') .'</p>';
      $output .= '<ul><li>'. t('support for enabling and disabling <a href="@modules">modules</a>. Drupal comes packaged with a number of core modules; each module provides a discrete set of features and may be enabled depending on the needs of your site. A wide array of additional modules contributed by members of the Drupal community are available for download at the <a href="@drupal-modules">Drupal.org module page</a>.', array('@modules' => url('admin/build/modules'), '@drupal-modules' => 'http://drupal.org/project/modules')) .'</li>';
      $output .= '<li>'. t('support for enabling and disabling <a href="@themes">themes</a>, which determine the design and presentation of your site. Drupal comes packaged with several core themes and additional contributed themes are available at the <a href="@drupal-themes">Drupal.org theme page</a>.', array('@themes' => url('admin/build/themes'), '@drupal-themes' => 'http://drupal.org/project/themes')) .'</li>';
      $output .= '<li>'. t('a robust <a href="@cache-settings">caching system</a> that allows the efficient re-use of previously-constructed web pages and web page components. Drupal stores the pages requested by anonymous users in a compressed format; depending on your site configuration and the amount of your web traffic tied to anonymous visitors, Drupal\'s caching system may significantly increase the speed of your site.', array('@cache-settings' => url('admin/settings/performance'))) .'</li>';
      $output .= '<li>'. t('a set of routine administrative operations that rely on a correctly-configured <a href="@cron">cron maintenance task</a> to run automatically. A number of other modules, including the feed aggregator, ping module and search also rely on <a href="@cron">cron maintenance tasks</a>. For more information, see the online handbook entry for <a href="@handbook">configuring cron jobs</a>.', array('@cron' => url('admin/reports/status'), '@handbook' => 'http://drupal.org/cron')) .'</li>';
      $output .= '<li>'. t('basic configuration options for your site, including <a href="@date-settings">date and time settings</a>, <a href="@file-system">file system settings</a>, <a href="@clean-url">clean URL support</a>, <a href="@site-info">site name and other information</a>, and a <a href="@site-maintenance">site maintenance</a> function for taking your site temporarily off-line.', array('@date-settings' => url('admin/settings/date-time'), '@file-system' => url('admin/settings/file-system'), '@clean-url' => url('admin/settings/clean-urls'), '@site-info' => url('admin/settings/site-information'), '@site-maintenance' => url('admin/settings/site-maintenance'))) .'</li></ul>';
      $output .= '<p>'. t('For more information, see the online handbook entry for <a href="@system">System module</a>.', array('@system' => 'http://drupal.org/handbook/modules/system/')) .'</p>';
      return $output;
    case 'admin':
      return '<p>'. t('Welcome to the administration section. Here you may control how your site functions.') .'</p>';
    case 'admin/by-module':
      return '<p>'. t('This page shows you all available administration tasks for each module.') .'</p>';
    case 'admin/build/themes':
      $output = '<p>'. t('Select which themes are available to your users and specify the default theme. To configure site-wide display settings, click the "configure" task above. Alternatively, to override these settings in a specific theme, click the "configure" link for that theme. Note that different themes may have different regions available for displaying content; for consistency in presentation, you may wish to enable only one theme.') .'</p>';
      $output .= '<p>'. t('To change the appearance of your site, a number of <a href="@themes">contributed themes</a> are available.', array('@themes' => 'http://drupal.org/project/themes')) .'</p>';
      return $output;
    case 'admin/build/themes/settings/'. $arg[4]:
      $reference = explode('.', $arg[4], 2);
      $theme = array_pop($reference);
      return '<p>'. t('These options control the display settings for the <code>%template</code> theme. When your site is displayed using this theme, these settings will be used. By clicking "Reset to defaults," you can choose to use the <a href="@global">global settings</a> for this theme.', array('%template' => $theme, '@global' => url('admin/build/themes/settings'))) .'</p>';
    case 'admin/build/themes/settings':
      return '<p>'. t('These options control the default display settings for your entire site, across all themes. Unless they have been overridden by a specific theme, these settings will be used.') .'</p>';
    case 'admin/build/modules':
      $output = '<p>'. t('Modules are plugins that extend Drupal\'s core functionality. Enable modules by selecting the <em>Enabled</em> checkboxes below and clicking the <em>Save configuration</em> button. Once a module is enabled, new <a href="@permissions">permissions</a> may be available. To reduce server load, modules with their <em>Throttle</em> checkbox selected are temporarily disabled when your site becomes extremely busy. (Note that the <em>Throttle</em> checkbox is only available if the Throttle module is enabled.)', array('@permissions' => url('admin/user/permissions')));
      if (module_exists('throttle')) {
        $output .= ' '. t('The auto-throttle functionality must be enabled on the <a href="@throttle">throttle configuration page</a> after having enabled the throttle module.', array('@throttle' => url('admin/settings/throttle')));
      }
      $output .= '</p>';
      $output .= '<p>'. t('It is important that <a href="@update-php">update.php</a> is run every time a module is updated to a newer version.', array('@update-php' => $base_url .'/update.php')) .'</p>';
      $output .= '<p>'. t('You can find all administration tasks belonging to a particular module on the <a href="@by-module">administration by module page</a>.', array('@by-module' => url('admin/by-module'))) .'</p>';
      $output .= '<p>'. t('To extend the functionality of your site, a number of <a href="@modules">contributed modules</a> are available.', array('@modules' => 'http://drupal.org/project/modules')) .'</p>';
      return $output;
    case 'admin/build/modules/uninstall':
      return '<p>'. t('The uninstall process removes all data related to a module. To uninstall a module, you must first disable it. Not all modules support this feature.') .'</p>';
    case 'admin/build/block/configure':
      if ($arg[4] == 'system' && $arg[5] == 0) {
        return '<p>'. t('The <em>Powered by Drupal</em> block is an optional link to the home page of the Drupal project. While there is absolutely no requirement that sites feature this link, it may be used to show support for Drupal.') .'</p>';
      }
      break;
    case 'admin/settings/actions':
    case 'admin/settings/actions/manage':
      $output = '<p>'. t('Actions are individual tasks that the system can do, such as unpublishing a piece of content or banning a user. Modules, such as the trigger module, can fire these actions when certain system events happen; for example, when a new post is added or when a user logs in. Modules may also provide additional actions.') .'</p>';
      $output .= '<p>'. t('There are two types of actions: simple and advanced. Simple actions do not require any additional configuration, and are listed here automatically. Advanced actions can do more than simple actions; for example, send an e-mail to a specified address, or check for certain words within a piece of content. These actions need to be created and configured first before they may be used. To create an advanced action, select the action from the drop-down below and click the <em>Create</em> button.') .'</p>';
      if (module_exists('trigger')) {
        $output .= '<p>'. t('You may proceed to the <a href="@url">Triggers</a> page to assign these actions to system events.', array('@url' => url('admin/build/trigger'))) .'</p>';
      }
      return $output;
    case 'admin/settings/actions/configure':
      return t('An advanced action offers additional configuration options which may be filled out below. Changing the <em>Description</em> field is recommended, in order to better identify the precise action taking place. This description will be displayed in modules such as the trigger module when assigning actions to system events, so it is best if it is as descriptive as possible (for example, "Send e-mail to Moderation Team" rather than simply "Send e-mail").');
    case 'admin/reports/status':
      return '<p>'. t("Here you can find a short overview of your site's parameters as well as any problems detected with your installation. It may be useful to copy and paste this information into support requests filed on drupal.org's support forums and project issue queues.") .'</p>';
  }
}
