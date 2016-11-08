<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       cabinetmedex/logbook.php
 *		\ingroup    cabinetmedex
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2016-11-07 23:09
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/cabinetmed/class/patient.class.php');
dol_include_once('/cabinetmedex/class/cabinetmedex.logbook.class.php');

// Load traductions files requiredby by page
$langs->load("cabinetmedex");
$langs->load("logbook@cabinetmedex");
$langs->load("other");

// Get parameters
$id				= GETPOST('id','int');
$socid			= GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');

$action		= GETPOST('action','alpha');
$backtopage = GETPOST('backtopage');

if (empty($action) && empty($id) && empty($ref)) $action='list';

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('cabinetmedexlogbooklist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('cabinetmedex');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// Load object if id or ref is provided as parameter
$object=new CabinetMedExLogbook($db);
if ($id > 0) {
	$object->fetch($id);
}

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction')) { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


if (empty($reshook))
{
	if ($socid > 0 && $action != 'list' && ! GETPOST('cancel'))
	{
		$error = 0;
		$message = '';
		if (  $action == 'add' )
		{
			$object->fk_societe = $socid;
			$object->content = GETPOST('content');
			$result=$object->create($user);
			$message = 'RecordSaved';
		} elseif ($action == 'save')
		{
			$object->content = GETPOST('content');
			$result=$object->update($user);
			$message = 'RecordSaved';
		// Action to delete
		} elseif ($action == 'confirm_delete')
		{
			$result=$object->delete($user);
			$message = 'RecordDeleted';
		}
		if ($result > 0)
		{
			// Delete OK
			setEventMessages($message, null, 'mesgs');
		}
		else
		{
			if (! empty($object->errors)) setEventMessages(null,$object->errors,'errors');
			else setEventMessages($object->error,null,'errors');
		}
	}
}




/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

$now=dol_now();

$form=new Form($db);

//$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
$help_url='';
$title = $langs->trans('PageLogBook');
llxHeader('', $title, $help_url);

// Put here content of your page
if ($socid > 0)
{
	$societe = new Patient($db);
	$societe->fetch($socid);
	$soc = $societe; // pour cabinetmed
	$object->fetchAllLines($socid);
	
	$head = societe_prepare_head($societe);
	dol_fiche_head($head, 'logbook', $langs->trans("Patient"),0,'patient@cabinetmed');
	
	print '<table class="border" width="100%">';
	
	$linkback = '<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'">'.$langs->trans("BackToList").'</a>';
	dol_banner_tab($societe, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');
	
	if ($societe->client)
	{
		print '<tr><td>';
		print $langs->trans('PatientCode').'</td><td colspan="3">';
		print $societe->code_client;
		if ($societe->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongPatientCode").')</font>';
		print '</td></tr>';
	}
	
	if ($societe->fournisseur)
	{
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $societe->code_fournisseur;
		if ($societe->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}
	
	print "</table>";
	
	dol_fiche_end();
	
	if ( ($action == 'addline' && $user->rights->cabinetmedex->logbook->add)
			|| ($action == 'edit' && $object->can($user,'edit'))) {
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		
		$formaction = $action == 'edit' ? 'save' : 'add';
		print '<input type="hidden" name="action" value="'.$formaction.'" />';
		print '<input type="hidden" name="socid" value="'.$socid.'" />';
		if ( $action == 'edit' ) {
			print '<input type="hidden" name="id" value="'.$id.'" />';
		}
		require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
		$doleditor=new DolEditor('content',$action == 'edit' ? $object->content : '','',200,'dolibarr_notes','In',true,false,$conf->global->FCKEDITOR_ENABLE_SOCIETE,20,70);
		$doleditor->Create(0);
		print '<center><br>';
		print '<input type="submit" class="button ignorechange" name="save" value="'.$langs->trans($action == 'edit' ? 'Save' : 'Add').'">';
		print '</center>';
		print "</form>\n<br>";
	} elseif ($action == 'delete' && $object->can($user,'delete')) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]
				. '?id=' . $id . '&amp:socid='.$socid, $langs->trans('DeleteMyOjbect'), $langs->trans('ConfirmDeleteMyObject'), 'confirm_delete', '', 0, 1);
		print $formconfirm;
	} elseif ($user->rights->cabinetmedex->logbook->add) {
		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=addline&amp;socid='.$socid.'">'.$langs->trans("AddLine").'</a>';
		print '</div>';
	}
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Author").'</td>';
	print '<td>'.$langs->trans("Date").'</td>';
	print '<td>'.$langs->trans("Content").'</td>';
	print '<td/>';
	print "</tr>\n";
	
	$var=True;
	foreach($object->lines as $line)
	{
		$author = new User($db);
		$author->fetch($line->fk_user_author);
		
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td>'.$author->getNomUrl(1).'</td>';
		print '<td>'.dol_print_date($line->datec,'dayhour').'</td>';
		print '<td>';
		print dol_textishtml($line->content)?$line->content:dol_nl2br($line->content,1,true);
		print '</td>';
		
		print '<td align="center">';
		if ( $line->can($user,'edit') ) {
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$line->id.'&amp;socid='.$socid.'&amp;page='.$page.'">';
			print img_edit();
			print '</a>';
		}
		if ( $line->can($user,'delete') ) {
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;id='.$line->id.'&amp;socid='.$socid.'&amp;page='.$page.'">';
			print img_delete();
			print '</a>';
		}
		print '</td>';
		
		print "</tr>";
	}
}

// End of page
llxFooter();
$db->close();
