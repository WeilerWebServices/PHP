<?php
/**
 * @package         Snippets
 * @version         6.6.2
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2019 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Object\CMSObject as JObject;

/**
 * Component Helper
 */
class SnippetsHelper
{
	public static $extension = 'com_snippets';

	/**
	 * Configure the Itembar.
	 *
	 * @param    string    The name of the active view.
	 */
	public static function addSubmenu($vName)
	{
		// No submenu for this component.
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return    JObject
	 */
	public static function getActions()
	{
		$user      = JFactory::getUser();
		$result    = new JObject;
		$assetName = 'com_snippets';

		$actions = [
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.state', 'core.delete',
		];

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Determines if the plugin for Snippets to work is enabled.
	 *
	 * @return    boolean
	 */
	public static function isEnabled()
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->quote('enabled'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
			->where($db->quoteName('element') . ' = ' . $db->quote('snippets'));

		$db->setQuery($query);

		return (boolean) $db->loadResult();
	}
}
