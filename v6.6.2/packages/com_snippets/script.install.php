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
use Joomla\Registry\AbstractRegistryFormat as JRegistryFormat;

require_once __DIR__ . '/script.install.helper.php';

class Com_SnippetsInstallerScript extends Com_SnippetsInstallerScriptHelper
{
	public $name           = 'SNIPPETS';
	public $alias          = 'snippets';
	public $extension_type = 'component';

	public function uninstall($adapter)
	{
		$this->uninstallPlugin($this->extname, 'system');
		$this->uninstallPlugin($this->extname, 'editors-xtd');
		$this->uninstallPlugin($this->extname, 'actionlog');
	}

	public function onAfterInstall($route)
	{
		$this->createTable();
		$this->fixOldFormatInDatabase();
		$this->disableEditorPlugin();
		$this->deleteOldFiles();
		$this->fixAssetsRules();

		return parent::onAfterInstall($route);
	}

	public function createTable()
	{
		$query = "CREATE TABLE IF NOT EXISTS `#__snippets` (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`alias` VARCHAR(100) NOT NULL,
			`name` VARCHAR(100) NOT NULL,
			`description` TEXT NOT NULL,
			`category` VARCHAR(50) NOT NULL,
			`content` TEXT NOT NULL,
			`params` TEXT NOT NULL,
			`published` TINYINT(1)  NOT NULL DEFAULT '0',
			`ordering` INT(11) NOT NULL DEFAULT '0',
			`checked_out` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (`id`),
			KEY `id` (`id`,`published`)
		) DEFAULT CHARSET=utf8;";
		$this->db->setQuery($query);
		$this->db->execute();
	}

	public function fixOldFormatInDatabase()
	{
		$query = 'SHOW FIELDS FROM ' . $this->db->quoteName('#__snippets');
		$this->db->setQuery($query);
		$columns = $this->db->loadColumn();

		if ( ! in_array('category', $columns))
		{
			$query = 'ALTER TABLE ' . $this->db->quoteName('#__snippets')
				. ' CHANGE COLUMN `alias` `alias` VARCHAR(100) NOT NULL AFTER `id`,'
				. ' CHANGE COLUMN `name` `name` VARCHAR(100) NOT NULL AFTER `alias`,'
				. ' ADD COLUMN `category` VARCHAR(50) NOT NULL AFTER `description`';
			$this->db->setQuery($query);
			$this->db->query();
		}

		// convert old J1.5 params syntax to new
		$query = $this->db->getQuery(true);
		$query->select('s.id, s.params')
			->from('#__snippets as s')
			->where('s.params REGEXP ' . $this->db->quote('^[^\{]'));
		$this->db->setQuery($query);
		$rows = $this->db->loadObjectList();
		foreach ($rows as $row)
		{
			if (empty($row->params))
			{
				continue;
			}

			$params = JRegistryFormat::getInstance('INI')->stringToObject($row->params);
			foreach ($params as $key => $val)
			{
				if (is_string($val) && ! (strpos($val, '|') === false))
				{
					$params->{$key} = explode('|', $val);
				}
			}
			$query = $this->db->getQuery(true);
			$query->update('#__snippets as s')
				->set('s.params = ' . $this->db->quote(json_encode($params)))
				->where('s.id = ' . (int) $row->id);
			$this->db->setQuery($query);
			$this->db->execute();
		}
	}

	public function disableEditorPlugin()
	{
		/* >>> [FREE] >>> */
		// disable the editor plugin on free version
		$query = $this->db->getQuery(true)
			->update('#__extensions')
			->set($this->db->quoteName('enabled') . ' = 0')
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('snippets'))
			->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('editors-xtd'));
		$this->db->setQuery($query);
		$this->db->execute();

		JFactory::getCache()->clean('_system');
	}

	private function deleteOldFiles()
	{
		$this->delete(
			[
				JPATH_SITE . '/components/com_snippets',
			]
		);
	}
}
