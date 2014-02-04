<?php

$res=@include("../../main.inc.php");					// For root directory
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

$langs->load("admin");
$langs->load("sendproductdoc@sendproductdoc");

// Security check
if (! $user->admin)
	accessforbidden();

$action	= GETPOST('action', 'alpha');

/*
 * Action
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

if (preg_match('/del_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

/*
 * View
 */

llxHeader('',$langs->trans("SendProductDocSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SendProductDocSetup"), $linkback, 'sendproductdoc@sendproductdoc');

print '<br>';

//$head = array();
//dol_fiche_head($head, '', $langs->trans("ModuleSetup"));

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

/*
 * Formulaire parametres divers
 */

// Allowed extensions
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ExtensionsAllowedToBeSend").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
print '<input type="text" name="SENDPRODUCTDOC_EXTENSIONS_OK" value="'.$conf->global->SENDPRODUCTDOC_EXTENSIONS_OK.'" size="50" />';
print '</td></tr>';

// Hide product description inside milestone
/*$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideByDefaultProductDescInsideMilestone").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if (! empty($conf->use_javascript_ajax))
{
	print ajax_constantonoff('MILESTONE_HIDE_PRODUCT_DESC');
}
else
{
	if (empty($conf->global->MILESTONE_HIDE_PRODUCT_DESC))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MILESTONE_HIDE_PRODUCT_DESC">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MILESTONE_HIDE_PRODUCT_DESC">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';*/

print '</table>';


llxFooter();
$db->close();
?>