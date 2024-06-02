<?php
/**
 * Template loader for use when we need to load templates from the plugin rather than the theme.
 * We still use Listeo's templates for many things, this is when we need our own.
 */
class Mrj_Global_Guest_Commission_Template_Loader extends Gamajo_Template_Loader {
 
	/**
	 * Prefix for filter names.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $filter_prefix = 'mrj_global_guest_commission';
 
	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $theme_template_directory = 'mrj-global-guest-commission';
 
	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $plugin_directory = MRJ_GLOBAL_GUEST_COMMISSION_PATH;

	/**
	   * Directory name where templates are found in this plugin.
	   *
	   * Can either be a defined constant, or a relative reference from where the subclass lives.
	   *
	   * e.g. 'templates' or 'includes/templates', etc.
	   *
	   * @since 1.1.0
	   *
	   * @var string
	   */
	  protected $plugin_template_directory = 'templates';
 
}

