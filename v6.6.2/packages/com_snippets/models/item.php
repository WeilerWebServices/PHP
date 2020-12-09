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
use Joomla\CMS\Form\Form as JForm;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\MVC\Model\AdminModel as JModelAdmin;
use Joomla\CMS\Table\Table as JTable;
use RegularLabs\Library\Date as RL_Date;
use RegularLabs\Library\Parameters as RL_Parameters;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;

jimport('joomla.application.component.modeladmin');

/**
 * Item Model
 */
class SnippetsModelItem extends JModelAdmin
{
	/**
	 * Constructor.
	 *
	 * @param    array    An optional associative array of configuration settings.
	 *
	 * @see        JController
	 */
	public function __construct()
	{
		$this->_config = RL_Parameters::getInstance()->getComponentParams('snippets');
		$this->_db     = JFactory::getDbo();

		parent::__construct();
	}

	/**
	 * @var        string    The prefix to use with controller messages.
	 */
	protected $text_prefix = 'RL';

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param    object $record A record object.
	 *
	 * @return    boolean    True if allowed to delete the record. Defaults to the permission set in the component.
	 */
	protected function canDelete($record)
	{
		if ($record->published != -2)
		{
			return false;
		}
		$user = JFactory::getUser();

		return $user->authorise('core.admin', 'com_snippets');
	}

	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param    object $record A record object.
	 *
	 * @return    boolean    True if allowed to change the state of the record. Defaults to the permission set in the component.
	 */
	protected function canEditState($record)
	{
		$user = JFactory::getUser();

		// Check the component since there are no categories or other assets.
		return $user->authorise('core.admin', 'com_snippets');
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param    type      The table type to instantiate
	 * @param    string    A prefix for the table class name. Optional.
	 * @param    array     Configuration array for model. Optional.
	 *
	 * @return    JTable    A database object
	 */
	public function getTable($type = 'Item', $prefix = 'SnippetsTable', $config = [])
	{
		JTable::addIncludePath(dirname(__DIR__) . '/tables');

		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param  array   $data     Data for the form.
	 * @param  boolean $loadData True if the form is to load its own data ( default case ), false if not.
	 *
	 * @return    JForm    A JForm object on success, false on failure
	 */
	public function getForm($data = [], $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			'com_snippets.item',
			'item',
			['control' => 'jform', 'load_data' => $loadData]
		);
		if (empty($form))
		{
			return false;
		}

		// Modify the form based on access controls.
		if ($this->canEditState((object ) $data) != true)
		{
			// Disable fields for display.
			$form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    mixed    The data for the form.
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_snippets.edit.item.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @return  mixed    Object on success, false on failure.
	 */
	public function getItem($pk = null, $getform = 0, $getdefaults = 0)
	{
		// Initialise variables.
		$pk    = ( ! empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');
		$table = $this->getTable();

		if ($pk > 0)
		{
			// Attempt to load the row.
			$return = $table->load($pk);

			// Check for a table object error.
			if ($return === false && $table->getError())
			{
				$this->setError($table->getError());

				return false;
			}
		}

		// Convert to the JObject before adding other data.
		$properties = $table->getProperties(1);
		$item       = JArrayHelper::toObject($properties, 'JObject');

		$isini  = ((substr($item->params, 0, 1) != '{') && (substr($item->params, -1, 1) != '}'));
		$params = RL_Parameters::getInstance()->getParams($item->params, JPATH_ADMINISTRATOR . '/components/com_snippets/item_params.xml');
		foreach ($params as $key => $val)
		{
			if ( ! isset($item->{$key}) && ! is_object($val))
			{
				$item->{$key} = $val;
			}
		}
		unset($item->params);

		if ($isini)
		{
			foreach ($item as $key => $val)
			{
				if (is_string($val) && $key != 'content')
				{
					$item->{$key} = stripslashes($val);
				}
			}
		}

		if ($getform)
		{
			$xmlfile = JPATH_ADMINISTRATOR . '/components/com_snippets/item_params.xml';
			$params  = new JForm('jform', ['control' => 'jform']);
			$params->loadFile($xmlfile, 1, '//config');
			$params->bind($item);
			$item->form = $params;
		}

		if ($getdefaults)
		{
			$item->defaults = RL_Parameters::getInstance()->getParams('', JPATH_ADMINISTRATOR . '/components/com_snippets/item_params.xml');
		}

		return $item;
	}

	/**
	 * Method to activate list.
	 *
	 * @param    array     An array of item ids.
	 * @param    string    The new URL to set for the snippets.
	 * @param    string    A comment for the snippets list.
	 *
	 * @return    boolean    Returns true on success, false on failure.
	 */
	public function activate(&$pks, $name)
	{
		// Initialise variables.
		$user = JFactory::getUser();

		// Sanitize the ids.
		$pks = ( array ) $pks;
		JArrayHelper::toInteger($pks);

		// Access checks.
		if ( ! $user->authorise('core.admin', 'com_snippets'))
		{
			$pks = [];
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));

			return false;
		}

		if ( ! empty($pks))
		{
			// Update the item rows.
			$this->_db->setQuery(
				'UPDATE `#__snippets`' .
				' SET `name` = ' . $this->_db->quote($name) . ', `published` = 1' .
				' WHERE `id` IN ( ' . implode(',', $pks) . ' )'
			);
			$this->_db->execute();

			// Check for a database error.
			if ($error = $this->_db->getErrorMsg())
			{
				$this->setError($error);

				return false;
			}
		}

		return true;
	}

	/**
	 * Method to validate form data.
	 */
	public function validate($form, $data, $group = null)
	{
		// Check for valid name
		if (empty($data['name']))
		{
			$this->setError(JText::_('SNP_THE_ITEM_MUST_HAVE_A_NAME'));

			return false;
		}

		if (empty($data['alias']))
		{
			$this->setError(JText::_('SNP_THE_ITEM_MUST_HAVE_AN_ID'));

			return false;
		}

		$newdata = [];
		$params  = [];
		$this->_db->setQuery('SHOW COLUMNS FROM #__snippets');
		$dbkeys = $this->_db->loadObjectList('Field');
		$dbkeys = array_keys($dbkeys);

		foreach ($data as $key => $val)
		{
			if (in_array($key, $dbkeys))
			{
				$newdata[$key] = $val;
			}
			else
			{
				$params[$key] = $val;
			}
		}

		$newdata['params'] = json_encode($params);

		return $newdata;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   12.2
	 */
	public function save($data)
	{
		$task = JFactory::getApplication()->input->get('task');

		if (in_array($task, ['apply', 'save']))
		{
			return parent::save($data);
		}

		$alias_free = false;

		while ( ! $alias_free)
		{
			// Check if incremented alias already exists
			$query = $this->_db->getQuery(true)
				->select('s.*')
				->from('#__snippets as s')
				->where('s.alias = ' . $this->_db->quote($data['alias']));
			$this->_db->setQuery($query, 0, 1);

			// Break if alias does not already exist
			if ( ! $this->_db->loadResult())
			{
				$alias_free = true;
				break;
			}

			// Get incremented title and alias
			$data['name']  = RL_String::increment($data['name']);
			$data['alias'] = RL_String::increment($data['alias'], 'dash');
		}

		$data['published'] = 0;

		return parent::save($data);
	}

	/**
	 * Method to copy an item
	 *
	 * @access    public
	 * @return    boolean    True on success
	 */
	public function copy($id)
	{
		$item = $this->getItem($id);

		unset($item->_errors);
		$item->id        = 0;
		$item->published = 0;

		$alias_free = false;

		while ( ! $alias_free)
		{
			// Get incremented title and alias
			$item->name  = RL_String::increment($item->name);
			$item->alias = RL_String::increment($item->alias, 'dash');

			// Check if incremented alias already exists
			$query = $this->_db->getQuery(true)
				->select('s.*')
				->from('#__snippets as s')
				->where('s.alias = ' . $this->_db->quote($item->alias));
			$this->_db->setQuery($query, 0, 1);

			// Break if alias does not already exist
			if ( ! $this->_db->loadResult())
			{
				$alias_free = true;
				break;
			}
		}

		$item = $this->validate(null, (array) $item);

		return $this->save($item);
	}

	public function replaceVars(&$str)
	{
		$this->replaceVarsUser($str);
		$this->replaceVarsDate($str);
		$this->replaceVarsRandom($str);
	}

	public function replaceVarsUser(&$str)
	{
		if (strpos($str, '[[user:') === false)
		{
			return;
		}
		RL_RegEx::match('\[\[user\:([^\]]+)\]\]', $str, $matches);

		if ( ! $matches)
		{
			return;
		}

		$user    = JFactory::getUser();
		$contact = null;
		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);

		foreach ($matches as $match)
		{
			if ($match[1] == 'password' || $match[1][0] == '_')
			{
				$str = str_replace($match[0], '', $str);
				continue;
			}

			if (isset($user->{$match[1]}))
			{
				$str = str_replace($match[0], $user->{$match[1]}, $str);
				continue;
			}

			if ( ! $contact)
			{
				$query->clear()
					->select('c.*')
					->from('#__' . $this->_config->contact_table . ' as c')
					->where('c.user_id = ' . (int) $user->id);
				$db->setQuery($query);
				$contact = $db->loadObject();
			}

			if (isset($contact->{$match[1]}))
			{
				$str = str_replace($match[0], $contact->{$match[1]}, $str);
				continue;
			}

			$str = str_replace($match[0], '', $str);
		}
	}

	public function replaceVarsDate(&$str)
	{

		if (strpos($str, '[[date:') === false)
		{
			return;
		}
		RL_RegEx::matchAll('\[\[date\:([^\]]+)\]\]', $str, $matches);

		if (empty($matches))
		{
			return;
		}

		foreach ($matches as $match)
		{
			if ($match[1] && strpos($match[1], '%') !== false)
			{
				$match[1] = RL_Date::strftimeToDateFormat($match[1]);
			}

			$replace = JHtml::_('date', time(), $match[1]);
			$str     = str_replace($match[0], $replace, $str);
		}
	}

	public function replaceVarsRandom(&$str)
	{
		if (strpos($str, '[[random:') === false)
		{
			return;
		}

		while (RL_RegEx::match('\[\[random\:([0-9]+)-([0-9]+)\]\]', $str, $match))
		{
			$search  = RL_RegEx::quote($match[0]);
			$replace = rand((int) $match[1], (int) $match[2]);
			$str     = RL_RegEx::replaceOnce($search, $replace, $str);
		}
	}
}
