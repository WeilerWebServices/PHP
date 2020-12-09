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
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\MVC\Controller\BaseController as JController;
use Joomla\CMS\Router\Route as JRoute;

/**
 * Master Display Controller
 */
class SnippetsController extends JController
{
	/**
	 * @var        string    The default view.
	 */
	protected $default_view = 'list';

	/**
	 * Method to display a view
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT . '/helpers/helper.php';

		// Load the submenu.
		SnippetsHelper::addSubmenu(JFactory::getApplication()->input->get('view', 'list'));

		$view   = JFactory::getApplication()->input->get('view', 'list');
		$layout = JFactory::getApplication()->input->get('layout', 'default');
		$id     = JFactory::getApplication()->input->getInt('id');

		// redirect to list if view is invalid
		if ($view != 'list' && $view != 'item')
		{
			$this->setRedirect(JRoute::_('index.php?option=com_snippets', false));

			return false;
		}

		// Check for edit form.
		if ($view == 'item' && $layout == 'edit' && ! $this->checkEditId('com_snippets.edit.item', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_snippets', false));

			return false;
		}

		parent::display();
	}
}
