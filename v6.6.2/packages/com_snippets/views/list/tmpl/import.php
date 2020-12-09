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

use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Router\Route as JRoute;

?>
<form onsubmit="return submitform();"
      action="<?php echo JRoute::_('index.php?option=com_snippets&view=list'); ?>" method="post"
      enctype="multipart/form-data" name="import-form" id="import-form">
	<fieldset class="form-horizontal">
		<legend><?php echo JText::_('RL_IMPORT_ITEMS'); ?></legend>
		<div class="control-group">
			<label for="file" class="control-label"><?php echo JText::_('SNP_CHOOSE_FILE'); ?></label>

			<div class="controls">
				<input class="input_box" id="file" name="file" type="file" size="57">
			</div>
		</div>
		<div class="control-group">
			<label for="publish_all" class="control-label"><?php echo JText::_('SNP_PUBLISH_ITEMS'); ?></label>

			<div class="controls">
				<fieldset id="publish_all" class="radio btn-group">
					<input type="radio" name="publish_all" id="publish_all0" value="0">
					<label for="publish_all0" class="btn btn-default"><?php echo JText::_('JNO'); ?></label>
					<input type="radio" name="publish_all" id="publish_all1" value="1">
					<label for="publish_all1" class="btn btn-default"><?php echo JText::_('JYES'); ?></label>
					<input type="radio" name="publish_all" id="publish_all2" value="2" checked="checked">
					<label for="publish_all2" class="btn btn-default"><?php echo JText::_('RL_AS_EXPORTED'); ?></label>
				</fieldset>
			</div>
		</div>
		<div class="form-actions">
			<input class="btn btn-primary" type="submit" value="<?php echo JText::_('RL_IMPORT'); ?>">
		</div>
	</fieldset>

	<input type="hidden" name="task" value="list.import">
	<?php echo JHtml::_('form.token'); ?>
</form>

<script language="javascript" type="text/javascript">
	/**
	 * Submit the admin form
	 *
	 * small hack: let task decides where it comes
	 */
	function submitform() {
		var file = jQuery('#file').val();
		if (file) {
			var dot = file.lastIndexOf(".");
			if (dot != -1) {
				var ext = file.substr(dot, file.length);
				if (ext == '.snbak') {
					return true;
				}
			}
		}
		alert('<?php echo JText::_('SNP_PLEASE_CHOOSE_A_VALID_FILE'); ?>');
		return false;
	}
</script>