<?php
// Classes and libraries for module system
//
// webtrees: Web based Family History software
// Copyright (C) 2012 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2010 John Finlay
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id$

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class clippings_WT_Module extends WT_Module implements WT_Module_Menu, WT_Module_Sidebar {
	// Extend class WT_Module
	public function getTitle() {
		return /* I18N: Name of a module */ WT_I18N::translate('Clippings cart');
	}

	// Extend class WT_Module
	public function getDescription() {
		return /* I18N: Description of the "Clippings cart" module */ WT_I18N::translate('Select records from your family tree and save them as a GEDCOM file.');
	}

	// Extend class WT_Module
	public function defaultAccessLevel() {
		return WT_PRIV_USER;
	}

	// Extend WT_Module
	public function modAction($mod_action) {
		switch($mod_action) {
		case 'ajax':
			header('Content-Type: text/html; charset=UTF-8');
			echo $this->getSidebarAjaxContent();
			break;
		case 'index':
			global $ENABLE_AUTOCOMPLETE, $MAX_PEDIGREE_GENERATIONS, $controller, $WT_SESSION;

			require_once WT_ROOT.WT_MODULES_DIR.'clippings/clippings_ctrl.php';
			require_once WT_ROOT.'includes/functions/functions_export.php';

			$clip_ctrl=new WT_Controller_Clippings();

			$controller=new WT_Controller_Base();
			$controller
				->setPageTitle($this->getTitle())
				->PageHeader();

			if ($ENABLE_AUTOCOMPLETE) require WT_ROOT.'js/autocomplete.js.htm';

			echo WT_JS_START;
			echo 'function radAncestors(elementid) {var radFamilies=document.getElementById(elementid);radFamilies.checked=true;}';
			echo WT_JS_END;

			if (!$WT_SESSION->cart[WT_GED_ID]) {
				echo '<h2>', WT_I18N::translate('Family Tree Clippings Cart'), '</h2>';
			}

			if ($clip_ctrl->action=='add') {
				$person = WT_GedcomRecord::getInstance($clip_ctrl->id);
				echo '<h3><a href="', $person->getHtmlUrl(), '">'.$person->getFullName(), '</a></h3>';
				if ($clip_ctrl->type=='fam') { ?>
					<form action="module.php" method="get">
					<input type="hidden" name="mod" value="clippings">
					<input type="hidden" name="mod_action" value="index">
					<table>
						<tr><td class="topbottombar"><?php echo WT_I18N::translate('Which other links from this family would you like to add?'); ?>
						<input type="hidden" name="id" value="<?php echo $clip_ctrl->id; ?>">
						<input type="hidden" name="type" value="<?php echo $clip_ctrl->type; ?>">
						<input type="hidden" name="action" value="add1"></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" checked value="none"><?php echo WT_I18N::translate('Add just this family record.'); ?></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" value="parents"><?php echo WT_I18N::translate('Add parents\' records together with this family record.'); ?></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" value="members"><?php echo WT_I18N::translate('Add parents\' and children\'s records together with this family record.'); ?></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" value="descendants"><?php echo WT_I18N::translate('Add parents\' and all descendants\' records together with this family record.'); ?></td></tr>
						<tr><td class="topbottombar"><input type="submit" value="<?php echo WT_I18N::translate('Continue Adding'); ?>"></td></tr>

					</table>
					</form>
				<?php }
				else if ($clip_ctrl->type=='indi') { ?>
					<form action="module.php" method="get">
					<input type="hidden" name="mod" value="clippings">
					<input type="hidden" name="mod_action" value="index">
					<table>
						<tr><td class="topbottombar"><?php echo WT_I18N::translate('Which links from this person would you also like to add?'); ?>
						<input type="hidden" name="id" value="<?php echo $clip_ctrl->id; ?>">
						<input type="hidden" name="type" value="<?php echo $clip_ctrl->type; ?>">
						<input type="hidden" name="action" value="add1"></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" checked value="none"><?php echo WT_I18N::translate('Add just this person.'); ?></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" value="parents"><?php echo WT_I18N::translate('Add this person, his parents, and siblings.'); ?></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" value="ancestors" id="ancestors"><?php echo WT_I18N::translate('Add this person and his direct line ancestors.'); ?><br>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo WT_I18N::translate('Number of generations:'); ?> <input type="text" size="5" name="level1" value="<?php echo $MAX_PEDIGREE_GENERATIONS; ?>" onfocus="radAncestors('ancestors');"></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" value="ancestorsfamilies" id="ancestorsfamilies"><?php echo WT_I18N::translate('Add this person, his direct line ancestors, and their families.'); ?><br >
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo WT_I18N::translate('Number of generations:'); ?> <input type="text" size="5" name="level2" value="<?php echo $MAX_PEDIGREE_GENERATIONS; ?>" onfocus="radAncestors('ancestorsfamilies');"></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" value="members"><?php echo WT_I18N::translate('Add this person, his spouse, and children.'); ?></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" value="descendants" id="descendants"><?php echo WT_I18N::translate('Add this person, his spouse, and all descendants.'); ?><br >
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo WT_I18N::translate('Number of generations:'); ?> <input type="text" size="5" name="level3" value="<?php echo $MAX_PEDIGREE_GENERATIONS; ?>" onfocus="radAncestors('descendants');"></td></tr>
						<tr><td class="topbottombar"><input type="submit" value="<?php echo WT_I18N::translate('Continue Adding'); ?>">
					</table>
					</form>
				<?php } else if ($clip_ctrl->type=='sour')  { ?>
					<form action="module.php" method="get">
					<input type="hidden" name="mod" value="clippings">
					<input type="hidden" name="mod_action" value="index">
					<table>
						<tr><td class="topbottombar"><?php echo WT_I18N::translate('Which records linked to this source should be added?'); ?>
						<input type="hidden" name="id" value="<?php echo $clip_ctrl->id; ?>">
						<input type="hidden" name="type" value="<?php echo $clip_ctrl->type; ?>">
						<input type="hidden" name="action" value="add1"></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" checked value="none"><?php echo WT_I18N::translate('Add just this source.'); ?></td></tr>
						<tr><td class="optionbox"><input type="radio" name="others" value="linked"><?php echo WT_I18N::translate('Add this source and families/people linked to it.'); ?></td></tr>
						<tr><td class="topbottombar"><input type="submit" value="<?php echo WT_I18N::translate('Continue Adding'); ?>">
					</table>
					</form>
				<?php }
				}

			if ($clip_ctrl->privCount>0) {
				echo "<span class=\"error\">".WT_I18N::translate('Some items could not be added due to privacy restrictions')."</span><br><br>";
			}

			if (!$WT_SESSION->cart[WT_GED_ID]) {

				// -- new lines, added by Jans, to display helptext when cart is empty
				if ($clip_ctrl->action!='add') {

					echo WT_I18N::translate('The Clippings Cart allows you to take extracts ("clippings") from this family tree and bundle them up into a single file for downloading and subsequent importing into your own genealogy program.  The downloadable file is recorded in GEDCOM format.<br /><ul><li>How to take clippings?<br />This is really simple. Whenever you see a clickable name (individual, family, or source) you can go to the Details page of that name. There you will see the <b>Add to Clippings Cart</b> option.  When you click that link you will be offered several options to download.</li><li>How to download?<br />Once you have items in your cart, you can download them just by clicking the <b>Download Now</b> link.  Follow the instructions and links.</li></ul>');

					echo WT_JS_START;
					echo 'var pastefield;';
					echo 'function paste_id(value) {pastefield.value=value;}';
					echo WT_JS_END;
					?>
					<form method="get" name="addin" action="module.php">
					<input type="hidden" name="mod" value="clippings">
					<input type="hidden" name="mod_action" value="index">
					<table>
					<tr>
						<td colspan="2" class="topbottombar" style="text-align:center; ">
							<?php echo WT_I18N::translate('Add Individual By ID'), help_link('add_by_id', $this->getName()); ?>
						</td>
					</tr>
					<tr>
						<td class="optionbox">
							<input type="hidden" name="action" value="add">
							<input type="text" name="id" id="cart_item_id" size="5">
						</td>
						<td class="optionbox">
							<?php echo print_findindi_link('cart_item_id'); ?>
							<?php print_findfamily_link('cart_item_id', ''); ?>
							<?php print_findsource_link('cart_item_id', ''); ?>
							<input type="submit" value="<?php echo WT_I18N::translate('Add'); ?>">

						</td>
					</tr>
					</table>
					</form>
					<?php
				}

				// -- end new lines
				echo WT_I18N::translate('Your Clippings Cart is empty.');
			} else {
				// Keep track of the INDI from the parent page, otherwise it will
				// get lost after ajax updates
				$pid=safe_GET_xref('pid');

				if ($clip_ctrl->action != 'download' && $clip_ctrl->action != 'add') { ?>
					<table><tr><td class="width33" valign="top" rowspan="3">
					<form method="get" action="module.php">
					<input type="hidden" name="mod" value="clippings">
					<input type="hidden" name="mod_action" value="index">
					<input type="hidden" name="action" value="download">
					<input type="hidden" name="pid" value="<?php echo $pid; ?>">
					<table>
					<tr><td colspan="2" class="topbottombar"><h2><?php echo WT_I18N::translate('File Information'); ?></h2></td></tr>
					<tr><td class="descriptionbox width50 wrap"><?php echo WT_I18N::translate('Zip File(s)'), help_link('zip'); ?></td>
					<td class="optionbox"><input type="checkbox" name="Zip" value="yes"></td></tr>

					<tr><td class="descriptionbox width50 wrap"><?php echo WT_I18N::translate('Include media (automatically zips files)'), help_link('include_media'); ?></td>
					<td class="optionbox"><input type="checkbox" name="IncludeMedia" value="yes"></td></tr>

					<?php if (WT_USER_GEDCOM_ADMIN) {	?>
						<tr><td class="descriptionbox width50 wrap"><?php echo WT_I18N::translate('Apply privacy settings?'), help_link('apply_privacy'); ?></td>
						<td class="list_value">
							<input type="radio" name="privatize_export" value="none" checked="checked"> <?php echo WT_I18N::translate('None'); ?><br>
							<input type="radio" name="privatize_export" value="gedadmin"> <?php echo WT_I18N::translate('Manager'); ?><br>
							<input type="radio" name="privatize_export" value="user"> <?php echo WT_I18N::translate('Member'); ?><br>
							<input type="radio" name="privatize_export" value="visitor"> <?php echo WT_I18N::translate('Visitor'); ?>
						</td></tr>
					<?php } elseif (WT_USER_CAN_ACCESS) {	?>
						<tr><td class="descriptionbox width50 wrap"><?php echo WT_I18N::translate('Apply privacy settings?'), help_link('apply_privacy'); ?></td>
						<td class="list_value">
							<input type="radio" name="privatize_export" value="user" checked="checked"> <?php echo WT_I18N::translate('Member'); ?><br>
							<input type="radio" name="privatize_export" value="visitor"> <?php echo WT_I18N::translate('Visitor'); ?>
						</td></tr>
					<?php } ?>

					<tr><td class="descriptionbox width50 wrap"><?php echo WT_I18N::translate('Convert from UTF-8 to ANSI (ISO-8859-1)'), help_link('utf8_ansi'); ?></td>
					<td class="optionbox"><input type="checkbox" name="convert" value="yes"></td></tr>

					<tr><td class="descriptionbox width50 wrap"><?php echo WT_I18N::translate('Convert media path to'), help_link('convertPath'); ?></td>
					<td class="list_value"><input type="text" name="conv_path" size="30" value="<?php echo $clip_ctrl->conv_path; ?>" dir="auto"></td></tr>

					<tr><td class="descriptionbox width50 wrap"><?php echo WT_I18N::translate('Convert media folder separators to'), help_link('convertSlashes'); ?></td>
					<td class="list_value">
					<input type="radio" name="conv_slashes" value="forward" <?php if ($clip_ctrl->conv_slashes=='forward') echo "checked=\"checked\" "; ?>>&nbsp;<?php echo WT_I18N::translate('Forward slashes : /'); ?><br>
					<input type="radio" name="conv_slashes" value="backward" <?php if ($clip_ctrl->conv_slashes=='backward') echo "checked=\"checked\" "; ?>>&nbsp;<?php echo WT_I18N::translate('Backslashes : \\'); ?>
					</td></tr>

					<tr><td class="topbottombar" colspan="2">
					<input type="submit" value="<?php echo WT_I18N::translate('Download Now'); ?>">
					</form>
					</td></tr>
					</table>
					</td></tr>
					</table>
					<br>

					<script type="text/javascript">
					<!--
					var pastefield;
					function paste_id(value)
					{
						pastefield.value=value;
					}
					//-->
					</script>
					<form method="get" name="addin" action="module.php">
					<input type="hidden" name="mod" value="clippings">
					<input type="hidden" name="mod_action" value="index">
					<table>
					<tr>
						<td colspan="2" class="topbottombar" style="text-align:center; ">
							<?php echo WT_I18N::translate('Add Individual By ID'), help_link('add_by_id', $this->getName()); ?>
						</td>
					</tr>
					<tr>
						<td class="optionbox">
							<input type="hidden" name="action" value="add">
							<input type="text" name="id" id="cart_item_id" size="8">
						</td>
						<td class="optionbox">
							<?php echo print_findindi_link('cart_item_id'); ?>
							<?php echo print_findfamily_link('cart_item_id'); ?>
							<?php echo print_findsource_link('cart_item_id'); ?>
							<input type="submit" value="<?php echo WT_I18N::translate('Add'); ?>">

						</td>
					</tr>
					</table>
					</form>


				<?php } ?>
				<br><a href="module.php?mod=clippings&amp;mod_action=index&amp;action=empty"><?php echo WT_I18N::translate('Empty Cart'); ?></a><?php echo help_link('empty_cart', $this->getName()); ?>
				</td></tr>

				<tr><td class="topbottombar"><h2><?php echo WT_I18N::translate('Family Tree Clippings Cart'), help_link('clip_cart', $this->getName()); ?></h2></td></tr>

				<tr><td valign="top">
				<table id="mycart" class="sortable list_table width100">
					<tr>
						<th class="list_label"><?php echo WT_I18N::translate('Name / Description'); ?></th>
						<th class="list_label"><?php echo WT_I18N::translate('Remove'); ?></th>
					</tr>
			<?php
				foreach (array_keys($WT_SESSION->cart[WT_GED_ID]) as $xref) {
					$record=WT_GedcomRecord::getInstance($xref);
					if ($record) {
						switch ($tag = $record->getType()) {
						case 'INDI': $icon='icon-indis'; break;
						case 'FAM': $icon='icon-sfamily'; break;
						case 'SOUR': $icon='icon-source'; break;
						case 'REPO': $icon='icon-repository'; break;
						case 'NOTE': $icon='icon-note'; break;
						case 'OBJE': $icon='icon-media'; break;
						default:     $icon='icon-clippings'; break;
						}
						?>
						<tr><td class="list_value">
							<i class="<?php echo $icon; ?>"></i>
						<?php
						echo '<a href="', $record->getHtmlUrl(), '">', $record->getFullName(), '</a>';
						?>
						</td>
						<td class="list_value center vmiddle"><a href="module.php?mod=clippings&amp;mod_action=index&amp;action=remove&amp;id=<?php echo $xref; ?>" class="icon-remove" title="<?php echo WT_I18N::translate('Remove'); ?>"></a></td>
					</tr>
					<?php
					}
				}
			?>
				</table>
				</td></tr></table>
			<?php
			}
			break;
		default:
			header('HTTP/1.0 404 Not Found');
			break;
		}
	}

	// Implement WT_Module_Menu
	public function defaultMenuOrder() {
		return 20;
	}

	// Implement WT_Module_Menu
	public function getMenu() {
		global $SEARCH_SPIDER, $controller;

		if ($SEARCH_SPIDER) {
			return null;
		}
		//-- main clippings menu item
		$menu = new WT_Menu($this->getTitle(), 'module.php?mod=clippings&amp;mod_action=index&amp;ged='.WT_GEDURL, 'menu-clippings', 'down');
		$submenu = new WT_Menu($this->getTitle(), 'module.php?mod=clippings&amp;mod_action=index&amp;ged='.WT_GEDURL, 'menu-clippingscart');
		$menu->addSubmenu($submenu);
		if (!empty($controller->record) && $controller->record->canDisplayDetails()) {
			$submenu = new WT_Menu(WT_I18N::translate('Add to clippings cart'), 'module.php?mod=clippings&amp;mod_action=index&amp;action=add&amp;id='.$controller->record->getXref(), 'menu-clippingsadd');
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}

	// Implement WT_Module_Sidebar
	public function defaultSidebarOrder() {
		return 60;
	}

	// Impelement WT_Module_Sidebar
	public function hasSidebarContent() {
		require_once WT_ROOT.WT_MODULES_DIR.'clippings/clippings_ctrl.php';

		// Creating a controller has the side effect of initialising the cart
		$clip_ctrl=new WT_Controller_Clippings();

		return true;
	}

	// Impelement WT_Module_Sidebar
	public function getSidebarContent() {
		return
			WT_JS_START.'		 	
			jQuery(document).ready(function() {
				jQuery(".add_cart, .remove_cart").live("click", function() {
					jQuery("#sb_clippings_content").load(this.href);
					return false;
				});
			});'.
			WT_JS_END.
			'<div id="sb_clippings_content">'.
			$this->getCartList().
			'</div>';
	}

	// Impelement WT_Module_Sidebar
	public function getSidebarAjaxContent() {
		require_once WT_ROOT.WT_MODULES_DIR.'clippings/clippings_ctrl.php';

		global $WT_SESSION;

		$clip_ctrl = new WT_Controller_Clippings();

		$add = safe_GET_xref('add','');
		$add1 = safe_GET_xref('add1','');
		$remove = safe_GET('remove', WT_REGEX_XREF);
		$others = safe_GET('others', WT_REGEX_ALPHANUM, '');
		$clip_ctrl->level1 = safe_GET('level1');
		$clip_ctrl->level2 = safe_GET('level2');
		$clip_ctrl->level3 = safe_GET('level3');
		if (!empty($add)) {
			$record = WT_GedcomRecord::getInstance($add);
			if ($record) {
				$clip_ctrl->id=$record->getXref();
				$clip_ctrl->type=$record->getType();
				$ret = $clip_ctrl->add_clipping($record);
				if ($ret) return $this->askAddOptions($record);
			}
		} elseif (!empty($add1)) {
			$record = WT_Person::getInstance($add1);
			if ($record) {
				$clip_ctrl->id=$record->getXref();
				$clip_ctrl->type=strtolower($record->getType());
				if ($others == 'parents') {
					foreach ($record->getChildFamilies() as $family) {
						$clip_ctrl->add_clipping($family);
						$clip_ctrl->add_family_members($family);
					}
				} elseif ($others == 'ancestors') {
					$clip_ctrl->add_ancestors_to_cart($record, $clip_ctrl->level1);
				} elseif ($others == 'ancestorsfamilies') {
					$clip_ctrl->add_ancestors_to_cart_families($record, $clip_ctrl->level2);
				} elseif ($others == 'members') {
					foreach ($record->getSpouseFamilies() as $family) {
						$clip_ctrl->add_clipping($family);
						$clip_ctrl->add_family_members($family);
					}
				} elseif ($others == 'descendants') {
					foreach ($record->getSpouseFamilies() as $family) {
						$clip_ctrl->add_clipping($family);
						$clip_ctrl->add_family_descendancy($family, $clip_ctrl->level3);
					}
				}
			}
		} elseif ($remove) {
			unset ($WT_SESSION->cart[WT_GED_ID][$remove]);
		} elseif (isset($_REQUEST['empty'])) {
			$WT_SESSION->cart[WT_GED_ID] = array ();
		} elseif (isset($_REQUEST['download'])) {
			return $this->downloadForm($clip_ctrl);
		}
		return $this->getCartList();
	}

	// A list for the side bar.
	public function getCartList() {
		global $WT_SESSION;

		// Keep track of the INDI from the parent page, otherwise it will
		// get lost after ajax updates
		$pid=safe_GET_xref('pid');

		if (!$WT_SESSION->cart[WT_GED_ID]) {
			$out=WT_I18N::translate('Your Clippings Cart is empty.');
		} else {
			$out='<ul>';
			foreach (array_keys($WT_SESSION->cart[WT_GED_ID]) as $xref) {
				$record=WT_GedcomRecord::getInstance($xref);
				if ($record && ($record->getType()=='INDI' || $record->getType()=='FAM')) { // Just show INDI/FAM in the sidbar
					switch ($tag = $record->getType()) {
					case 'INDI': $icon='icon-indis'; break;
					case 'FAM': $icon='icon-sfamily'; break;
					case 'SOUR': $icon='icon-source'; break;
					case 'REPO': $icon='icon-repository'; break;
					case 'NOTE': $icon='icon-note'; break;
					case 'OBJE': $icon='icon-media'; break;
					default:     $icon='icon-clippings'; break;
					}
					$out .= '<li>';
					if (!empty($icon)) {
						$out .= '<i class="'.$icon.'"></i>';
					}
					$out .= '<a href="'.$record->getHtmlUrl().'">';
					if ($record->getType()=="INDI") $out .=$record->getSexImage();
					$out .= ' '.$record->getFullName().' ';
					if ($record->getType()=="INDI" && $record->canDisplayDetails()) {
						$out .= ' ('.$record->getLifeSpan().')';
					}
					$out .= '</a>';
					$out .= '<a class="icon-remove remove_cart" href="module.php?mod='.$this->getName().'&amp;mod_action=ajax&amp;sb_action=clippings&amp;remove='.$xref.'&amp;pid='.$pid.'" title="'.WT_I18N::translate('Remove').'"></a>';
					$out .='</li>';
				}
			}
			$out.='</ul>';
		}

		if ($WT_SESSION->cart[WT_GED_ID]) {
			$out.=
				'<br><a href="module.php?mod='.$this->getName().'&amp;mod_action=ajax&amp;sb_action=clippings&amp;empty=true&amp;pid='.$pid.'" class="remove_cart">'.
				WT_I18N::translate('Empty Cart').
				'</a>'.help_link('empty_cart', $this->getName()).
				'<br>'.
				'<a href="module.php?mod='.$this->getName().'&amp;mod_action=ajax&amp;sb_action=clippings&amp;download=true&amp;pid='.$pid.'" class="add_cart">'.
				WT_I18N::translate('Download Now').
				'</a>';
		}
		$record=WT_Person::getInstance($pid);
		if ($record && !array_key_exists($record->getXref(), $WT_SESSION->cart[WT_GED_ID])) {
			$out .= '<br><a href="module.php?mod='.$this->getName().'&amp;mod_action=ajax&amp;sb_action=clippings&amp;add='.$pid.'&amp;pid='.$pid.'" class="add_cart"><i class="icon-clippings"></i> '.WT_I18N::translate('Add %s to cart', $record->getFullName()).'</a>';
		}
		return $out;
	}
	public function askAddOptions($person) {
		global $MAX_PEDIGREE_GENERATIONS;
		$out = '<h3><a href="'.$person->getHtmlUrl().'">'.$person->getFullName().'</a></h3>';
		$out .= WT_JS_START;
		$out .= 'function radAncestors(elementid) {var radFamilies=document.getElementById(elementid);radFamilies.checked=true;}
			function continueAjax(frm) {
				var others = jQuery("input[name=\'others\']:checked").val();
				var link = "module.php?mod='.$this->getName().'&mod_action=ajax&sb_action=clippings&add1="+frm.pid.value+"&others="+others+"&level1="+frm.level1.value+"&level2="+frm.level2.value+"&level3="+frm.level3.value;
				jQuery("#sb_clippings_content").load(link);
			}';
		$out .= WT_JS_END;
		if ($person->getType()=='FAM') {

			$out .= '<form action="module.php" method="get" onsubmit="continueAjax(this); return false;">
			<input type="hidden" name="mod" value="clippings">
			<input type="hidden" name="mod_action" value="index">
			<table>
			<tr><td class="topbottombar">'.WT_I18N::translate('Which other links from this family would you like to add?').'
			<input type="hidden" name="pid" value="'.$person->getXref().'">
			<input type="hidden" name="type" value="'.$person->getType().'">
			<input type="hidden" name="action" value="add1"></td></tr>
			<tr><td class="optionbox"><input type="radio" name="others" checked value="none">'.WT_I18N::translate('Add just this family record.').'</td></tr>
			<tr><td class="optionbox"><input type="radio" name="others" value="parents">'.WT_I18N::translate('Add parents\' records together with this family record.').'</td></tr>
			<tr><td class="optionbox"><input type="radio" name="others" value="members">'.WT_I18N::translate('Add parents\' and children\'s records together with this family record.').'</td></tr>
			<tr><td class="optionbox"><input type="radio" name="others" value="descendants">'.WT_I18N::translate('Add parents\' and all descendants\' records together with this family record.').'</td></tr>
			<tr><td class="topbottombar"><input type="submit" value="'.WT_I18N::translate('Continue Adding').'"></td></tr>
			</table>
			</form>';
		}
		else if ($person->getType()=='INDI') {
			$out .= '<form action="module.php" method="get" onsubmit="continueAjax(this); return false;">
			<input type="hidden" name="mod" value="clippings">
			<input type="hidden" name="mod_action" value="index">
		'.WT_I18N::translate('Which links from this person would you also like to add?').'
		<input type="hidden" name="pid" value="'.$person->getXref().'">
		<input type="hidden" name="type" value="'.$person->getType().'">
		<input type="hidden" name="action" value="add1">
		<ul>
		<li><input type="radio" name="others" checked value="none">'.WT_I18N::translate('Add just this person.').'</li>
		<li><input type="radio" name="others" value="parents">'.WT_I18N::translate('Add this person, his parents, and siblings.').'</li>
		<li><input type="radio" name="others" value="ancestors" id="ancestors">'.WT_I18N::translate('Add this person and his direct line ancestors.').'<br>
				'.WT_I18N::translate('Number of generations:').'<input type="text" size="4" name="level1" value="'.$MAX_PEDIGREE_GENERATIONS.'" onfocus="radAncestors(\'ancestors\');"></li>
		<li><input type="radio" name="others" value="ancestorsfamilies" id="ancestorsfamilies">'.WT_I18N::translate('Add this person, his direct line ancestors, and their families.').'<br>
				'.WT_I18N::translate('Number of generations:').' <input type="text" size="4" name="level2" value="'. $MAX_PEDIGREE_GENERATIONS.'" onfocus="radAncestors(\'ancestorsfamilies\');"></li>
		<li><input type="radio" name="others" value="members">'.WT_I18N::translate('Add this person, his spouse, and children.').'</li>
		<li><input type="radio" name="others" value="descendants" id="descendants">'.WT_I18N::translate('Add this person, his spouse, and all descendants.').'<br >
				'.WT_I18N::translate('Number of generations:').' <input type="text" size="4" name="level3" value="'.$MAX_PEDIGREE_GENERATIONS.'" onfocus="radAncestors(\'descendants\');"></li>
		</ul>
		<input type="submit" value="'.WT_I18N::translate('Continue Adding').'">
		</form>';
		} else if ($person->getType()=='SOUR')  {
			$out .= '<form action="module.php" method="get" onsubmit="continueAjax(this); return false;">
		<input type="hidden" name="mod" value="clippings">
		<input type="hidden" name="mod_action" value="index">
		<table>
		<tr><td class="topbottombar">'.WT_I18N::translate('Which records linked to this source should be added?').'
		<input type="hidden" name="pid" value="'.$person->getXref().'">
		<input type="hidden" name="type" value="'.$person->getType().'">
		<input type="hidden" name="action" value="add1"></td></tr>
		<tr><td class="optionbox"><input type="radio" name="others" checked value="none">'.WT_I18N::translate('Add just this source.').'</td></tr>
		<tr><td class="optionbox"><input type="radio" name="others" value="linked">'.WT_I18N::translate('Add this source and families/people linked to it.').'</td></tr>
		<tr><td class="topbottombar"><input type="submit" value="'.WT_I18N::translate('Continue Adding').'">
		</table>
		</form>';
		}
		else return $this->getSidebarContent();
		return $out;
	}

	public function downloadForm($clip_ctrl) {
		$pid=safe_GET_xref('pid');

		$out = WT_JS_START;
		$out .= 'function cancelDownload() {
				var link = "module.php?mod='.$this->getName().'&mod_action=ajax&sb_action=clippings&pid='.$pid.'";
				jQuery("#sb_clippings_content").load(link);
			}';
		$out .= WT_JS_END;
		$out .= '<form method="get" action="module.php">
		<input type="hidden" name="mod" value="clippings">
		<input type="hidden" name="mod_action" value="index">
		<input type="hidden" name="pid" value="'.$pid.'">
		<input type="hidden" name="action" value="download">
		<table>
		<tr><td colspan="2" class="topbottombar"><h2>'.WT_I18N::translate('File Information').'</h2></td></tr>
		<tr><td class="descriptionbox width50 wrap">'.WT_I18N::translate('Zip File(s)').help_link('zip').'</td>
		<td class="optionbox"><input type="checkbox" name="Zip" value="yes" checked="checked"></td></tr>

		<tr><td class="descriptionbox width50 wrap">'.WT_I18N::translate('Include media (automatically zips files)').help_link('include_media').'</td>
		<td class="optionbox"><input type="checkbox" name="IncludeMedia" value="yes" checked="checked"></td></tr>
		';

		if (WT_USER_GEDCOM_ADMIN) {
			$out.=
				'<tr><td class="descriptionbox width50 wrap">'.WT_I18N::translate('Apply privacy settings?').help_link('apply_privacy').'</td>'.
				'<td class="list_value">'.
				'	<input type="radio" name="privatize_export" value="none" checked="checked"> '.WT_I18N::translate('None').'<br>'.
				'	<input type="radio" name="privatize_export" value="gedadmin"> '.WT_I18N::translate('Manager').'<br>'.
				'	<input type="radio" name="privatize_export" value="user"> '.WT_I18N::translate('Member').'<br>'.
				'	<input type="radio" name="privatize_export" value="visitor"> '.WT_I18N::translate('Visitor').
				'</td></tr>';
		} elseif (WT_USER_CAN_ACCESS) {
				'<tr><td class="descriptionbox width50 wrap">'.WT_I18N::translate('Apply privacy settings?').help_link('apply_privacy').'</td>'.
				'<td class="list_value">'.
				'	<input type="radio" name="privatize_export" value="user" checked="checked"> '.WT_I18N::translate('Member').'<br>'.
				'	<input type="radio" name="privatize_export" value="visitor"> '.WT_I18N::translate('Visitor').
				'</td></tr>';
		}

		$out .='
		<tr><td class="descriptionbox width50 wrap">'.WT_I18N::translate('Convert from UTF-8 to ANSI (ISO-8859-1)').help_link('utf8_ansi').'</td>
		<td class="optionbox"><input type="checkbox" name="convert" value="yes"></td></tr>

		<input type="hidden" name="conv_path" value="'.$clip_ctrl->conv_path.'"></td></tr>

		<tr><td class="topbottombar" colspan="2">
		<input type="button" value="'.WT_I18N::translate('Cancel').'" onclick="cancelDownload();">
		<input type="submit" value="'.WT_I18N::translate('Download Now').'">
		</form>';

		return $out;
	}

}
