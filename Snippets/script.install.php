<?php
/**
 * @package         Regular Labs Installer
 * @version         19.10.23919
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2019 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

if ( ! class_exists('RegularLabsInstaller'))
{
	require_once __DIR__ . '/script.helper.php';
}

class PlgSystemRegularLabsInstallerSnippetsInstallerScript extends RegularLabsInstaller
{
	var $dir           = null;
	var $installerName = 'regularlabsinstallersnippets';

	public function __construct()
	{
		$this->dir = __DIR__;
	}
}
