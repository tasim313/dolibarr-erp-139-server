<?php
/* Copyright (C) 2011-2013   Stephen Larroque <lrq3000@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/admin/customfields.php
 *	\ingroup    others
 *	\brief          Configuring page for custom fields (add/delete/edit custom fields)
 */

// **** INIT ****
$res=0;
if (! $res && file_exists(dirname(__FILE__)."/../main.inc.php")) $res=@include(dirname(__FILE__)."/../main.inc.php");			// for root directory
if (! $res && file_exists(dirname(__FILE__)."/../../main.inc.php")) $res=@include(dirname(__FILE__)."/../../main.inc.php");		// for level1 directory ("custom" directory)
if (! $res && file_exists(dirname(__FILE__)."/../../../main.inc.php")) $res=@include(dirname(__FILE__)."/../../../main.inc.php");	// for level2 directory
if (! $res) die("Include of main fails");

require(dirname(__FILE__).'/../conf/conf_customfields.lib.php');
require_once(dirname(__FILE__).'/../class/customfields.class.php');
require_once(dirname(__FILE__).'/../lib/customfields_printforms.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php');

// Security check
if (!$user->admin)
accessforbidden();

// **** MAIN VARS ****
// -- Getting the current active module
if (!(GETPOST("module"))) {
    $currentmodule = reset($modulesarray); // reset($array) gets the first value of the array, use key() to get the first key
} else {
    if (in_array(GETPOST("module"), $modulesarray)) { // protection to avoid sql injection (can only request referenced modules)
        $currentmodule = GETPOST("module");
    } else {
        $currentmodule = $modulesarray[0];
    }
}

$action = GETPOST("action");

// Get and set default values otherwise for checkable options
if (count($_POST["nulloption"]) == 1)  {$nulloption = true;} else {$nulloption = false;}

// **** INIT CUSTOMFIELD CLASS ****
$customfields = new CustomFields($db, $currentmodule);

// **** ACTIONS ****
if ($action == "set")
{
    Header("Location: customfields.php"); // TODO: what's this????
    exit;
}

// Initialization of the module's customfields (we create the customfields table for this module)
if ($action == 'init') {
    $rtncode = $customfields->initCustomFields();
    if ($rtncode > 0 and !count($customfields->errors)) { // If no error, we refresh the page
        Header("Location: ".$_SERVER["PHP_SELF"]."?module=".$currentmodule);
        exit();
    } else { // else we print the errors
        $error++;
        $mesg=$customfields->error;
    }
}

// Add/Update a field
if ($action == 'add' or $action == 'update') {
    if ($_POST["button"] != $langs->trans("Cancel")) {
        // Check values
        if (GETPOST('size') < 0) { // We accept 0 for infinity (for text type)
            $error++;
            $langs->load("errors");
            $mesg=$langs->trans("ErrorSizeTooLongForVarcharType");
            if ($action == 'add') { // we set back the previous action so that the user can go back to edit and fix the mistakes
                $action = 'create';
            } elseif ($action == 'update') {
                $action = 'edit';
            }
        }
        // Setting extra options
        $extra = new stdClass();

        if (! $error) {
            // We check that the field name does not contain any special character (only alphanumeric)
            if (isset($_POST["field"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/i",$_POST['field'])) { // note that we also force the field name (which is the sql column name) to be lowercase
                // Calling the action function
                if ($action == 'add') {
                    $result=$customfields->addCustomField(strtolower($_POST['field']),$_POST['type'],$_POST['size'],$nulloption,$_POST['defaultvalue'],$_POST['constraint'],$_POST['customtype'],$_POST['customdef'],$_POST['customsql'], null, $extra);
                } elseif ($action == 'update') {
                    $result=$customfields->updateCustomField($_POST['fieldid'], strtolower($_POST['field']),$_POST['type'],$_POST['size'],$nulloption,$_POST['defaultvalue'],$_POST['constraint'],$_POST['customtype'],$_POST['customdef'],$_POST['customsql'], $extra);
                }
                // Error ?
                if ($result > 0 and !count($customfields->errors)) { // If no error, we refresh the page
                    Header("Location: ".$_SERVER["PHP_SELF"]."?module=".$currentmodule);
                    exit();
                } else { // else we show the error
                    $error++;
                    $mesg=$customfields->error;
                }
            } else {
                $error++;
                $langs->load("errors");
                $mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("FieldName"));
                if ($action == 'add') { // we set back the previous action so that the user can go back to edit and fix the mistakes
                    $action = 'create';
                } elseif ($action == 'update') {
                    $action = 'edit';
                }
            }
        }
    }
}

// Confirmation form to delete a field
$form = new Form($db);
if ($action == 'delete')
{
    $field = $customfields->fetchFieldStruct($_GET["fieldid"]);
    $text=$langs->trans('ConfirmDeleteCustomField').' '.$field->column_name."<br />".$langs->trans('CautionDataLostForever');
    $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"]."?fieldid=".$_GET["fieldid"]."&module=".$currentmodule,$langs->trans('DeleteCustomField'),$text,'confirm_delete',null,'no',1);
}

// Deleting a field
if ($action == 'confirm_delete') {
    if(isset($_GET["fieldid"])) {
        $result=$customfields->deleteCustomField($_GET["fieldid"]);
        if ($result >= 0 and !count($customfields->errors)) {
            Header("Location: ".$_SERVER["PHP_SELF"]."?module=".$currentmodule);
            exit();
        } else {
            $mesg=$customfields->error;
        }
    } else {
        $error++;
        $langs->load("errors");
        $mesg=$langs->trans("ErrorNoFieldSelected",$langs->transnoentities("AttributeCode"));
    }
}

// Moving customfields action (changing the order)
if ($action == 'move' and !empty($_GET['offset']) and is_numeric($_GET['offset'])) {
    if(isset($_GET["fieldid"])) {
        $offset = $_GET['offset'];
        $fieldid = $_GET["fieldid"];

        $extra = new stdClass();
        $field = $customfields->fetchFieldStruct($_GET["fieldid"]);
        if (!isset($field->extra->position)) {
            $extra->position = $field->ordinal_position + $offset;
        } else {
            $extra->position = $field->extra->position + $offset;
        }
        $result = $customfields->setExtra($fieldid, $extra);

        if ($result < 0 or count($customfields->errors) > 0) $mesg=$customfields->error;
    } else {
        $error++;
        $langs->load("errors");
        $mesg=$langs->trans("ErrorNoFieldSelected",$langs->transnoentities("AttributeCode"));
    }
}

/*
 *	View
 */

// necessary headers
$html=new Form($db);

llxHeader('',$langs->trans("CustomFieldsSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

// print title
print_fiche_titre($langs->trans("CustomFieldsSetup"),$linkback,'setup');

// print long description (to help first time users and provide with a link to the wiki, kind of a contextual help) - but only if it's the customfields admin page
dol_fiche_head();
print($langs->trans('Description').":<br />".$langs->trans("CustomFieldsLongDescriptionWithAd"));
dol_fiche_end();

if (isset($formconfirm)) print $formconfirm;

$head = customfields_admin_prepare_head($modulesarray, $currentmodule); // draw modules tabs

// Print error messages that can be returned by various functions
if (function_exists('setEventMessage')) {
    setEventMessage($mesg, 'errors'); // New way since Dolibarr v3.3
} else {
    dol_htmloutput_errors($mesg); // Old way to print error messages
}

// Probing if the customfields table exists for this module
$tableexists = $customfields->probeTable();

// if the table for this module is not created, we ask user if he wants to create it
if (!$tableexists) {
    print $langs->trans("TableDoesNotExist");
    print "<br /><center><a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?module=".$currentmodule."&action=init\">".$langs->trans("CreateTable")."</a></center>";
    dol_fiche_end();

// else, the table exists and we can proceed to show the customfields
} else {
    // start of the form
    //print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?module='.$currentmodule.'">';
    //print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    // end of necessary headers

    // start of the fields table
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    // table headers
    print '<td width="30%">'.$langs->trans("Fieldname").'</td>';
    print '<td width="30%">'.$langs->trans("Datatype").'</td>';
    print '<td width="30%">'.$langs->trans("Variable").'</td>';
    print '<td width="10%"></td>'; // Empty header because this column will be used to show the delete button
    // end of table headers
    print "</tr>";

    // generating custom fields list
    $fieldsarray = $customfields->fetchAllFieldsStruct();

    if ($fieldsarray < 0) { // error
        $error++;
        $customfields->printErrors();
    } else {
        // generated rows of the table
        $i = 0; // used to alternate background color
        if (count($fieldsarray) > 0) {
            foreach ($fieldsarray as $obj) {
                if ($obj->column_name != 'rowid' and $obj->column_name != 'fk_'.$currentmodule) // we skip the rowid and fk_facture rows which are not custom fields
                {
                    if ($i % 2  == 0) {$colorclass = 'impair';} else {$colorclass = 'pair';} // for more visibility, we switch the background color each row
                    print '<tr class="'.$colorclass.'">';
                    print '<td>'.$obj->column_name.'</td>';
                    print '<td align="left">';
                    print $obj->column_type;
                    print '</td>';
                    print '<td align="left">';
                    print $customfields->varprefix.$obj->column_name;
                    print '</td>';
                    print '<td align="center">';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?module='.$currentmodule.$tabembedded.'&action=move&offset=-1&fieldid='.$obj->ordinal_position.'">'.img_up().'</a>';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?module='.$currentmodule.$tabembedded.'&action=move&offset=1&fieldid='.$obj->ordinal_position.'">'.img_down().'</a>';
                    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?module='.$currentmodule.'&action=edit&fieldid='.$obj->ordinal_position.'">'.img_edit().'</a>';
                    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?module='.$currentmodule.'&action=delete&fieldid='.$obj->ordinal_position.'">'.img_delete().'</a>';
                    print '</td>';
                    print '</tr>';
                    $i++;
                }
            }
        }
        // end of the generated rows
    }

    print '</table>';
    // end of the fields table
    //print '</form>';
    // end of the form

?>

<br>

<?php
    dol_fiche_end();
    // end of necessary footers


    /*
     * Barre d'actions
     *
     */
    if ($action != 'create')
    {
        print '<div class="tabsAction">';
        print "<a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?module=".$currentmodule."&action=create\">".$langs->trans("NewField")."</a>";
        print "</div>";
    }
}

/* ************************************************************************** */
/*                                                                            */
/* Creation d'un champ optionnel
 /*                                                                            */
/* ************************************************************************** */
;
if ($action == 'create' or ($action == 'edit' and GETPOST('fieldid')) ) {
    print "<br>";

    // ** Page header title and field fetching from db
    if ($action == 'create') {
        print_titre($langs->trans('NewField'));
    } elseif ($action == 'edit') {
        $fieldobj = $customfields->fetchFieldStruct($_GET["fieldid"]); // fetching the field data
        print_titre($langs->trans('FieldEdition',$fieldobj->column_name));
    }

    // ** Form and hidden fields
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="module" value="'.$currentmodule.'">';
    print '<table summary="listofattributes" class="border" width="100%">';

    if ($action == 'create') {
        print '<input type="hidden" name="action" value="add">';
    } elseif ($action == 'edit') {
        print '<input type="hidden" name="fieldid" value="'.GETPOST('fieldid').'">';
        print '<input type="hidden" name="action" value="update">';
    }

    // ** Variables initializing
    if ($action == 'create') {
        $field_name = GETPOST('field'); // if GETPOST is defined, $field_name will reload the data submitted by the admin, else if there's none it will just be empty (blank creation of a custom field). This is a clever way to avoid too many conditionnal statements.
        $field_type = GETPOST('type');
        $field_size = GETPOST('size');
        $field_constraint = GETPOST('constraint');
        $field_customtype = GETPOST('customtype');
        $checked = '';
        if (empty($_POST)) {
            $checked = "checked=checked"; //  By default a field can be null (necessary to have the field either possibly null or to have a default value if the user add a new field while he already saved an invoice/propal/whatever with custom fields, these already saved records must know what to set by default for the new column)
        } elseif (count($_POST["nulloption"]) == 1) {
            $checked = "checked=checked"; // if the user created the custom field but there was an error submitting it, we must be able to reload the settings so that the user can fix the problem and resubmit
        }
    } elseif ($action == 'edit') {
        if (GETPOST('field')) $field_name = GETPOST('field'); else $field_name = $fieldobj->column_name;
        if (GETPOST('type')) $field_type = GETPOST('type'); else $field_type = $fieldobj->data_type;
        if (!array_key_exists($field_type, $sql_datatypes)) { // if the admin supplied a custom field type, we assign it to the right field ($field_customtype)
            $field_customtype = $field_type;
            $field_type = 'other';
        }
        if (GETPOST('size')) $field_size = GETPOST('size'); else $field_size = $fieldobj->size;
        if (count($_POST["nulloption"]) == 1) $checked = "checked=checked"; else $checked = (strtolower($fieldobj->is_nullable) == 'yes' ? "checked=checked" : '');
        if (GETPOST('defaultvalue')) $field_defaultvalue = GETPOST('defaultvalue'); else $field_defaultvalue = $fieldobj->column_default;
        if (GETPOST('constraint')) $field_constraint = GETPOST('constraint'); else $field_constraint = $fieldobj->referenced_table_name;
    }

    // ** User Fields
    // Label (to be defined in lang file)
    if ($customfields->findLabel($field_name) != $field_name) { // detecting if the label has been defined
        $field_label = $customfields->findLabel($field_name); // if it's different, then it's been defined
    } elseif ( !empty($field_name) )  { // else if the field has been defined but not the label, we show the code
        $field_label = $field_name.'<br />('.$langs->trans("PleaseEditLangFile").' - code : <b>'.$customfields->varprefix.$field_name.'</b> '.$langs->trans("or").' <b>'.$field_name.'</b>)';
    } else {
        $field_label = $field_name.' ('.$langs->trans("PleaseEditLangFile").')'; // else if it's the same string returned by $langs->trans(), then it's probably because it's not defined
    }
    print '<tr><td class="field">'.$langs->trans("Label").'</td><td class="valeur">'.$field_label.'</td></tr>';
    // Field name in sql table
    print '<tr><td class="fieldrequired required">'.$langs->trans("FieldName").' ('.$langs->trans("AlphaNumOnlyCharsAndNoSpace").')</td><td class="valeur"><input type="text" name="field" size="10" value="'.$field_name.'"></td></tr>';
    // Type and custom type
    print '<tr><td class="fieldrequired required">'.$langs->trans("Type").'</td><td class="valeur">';
    print $html->selectarray('type',$sql_datatypes,$field_type);
    print '<br>'.$langs->trans('Other').' ('.$langs->trans('CustomSQL').'): <input type="text" name="customtype" size="10" value="'.$field_customtype.'">';
    print '</td></tr>';
    // Size
    print '<tr><td class="field">'.$langs->trans("Size").', '.$langs->trans("or").' '.$langs->trans("EnumValues").' ('.$langs->trans("SizeDesc").')<br />'.$langs->trans("SizeNote").'</td><td><input type="text" name="size" size="10" value="'.$field_size.'"></td></tr>';
    // Null?
    print '<tr><td class="field">'.$langs->trans("CanBeNull?").'</td><td><input type="checkbox" name="nulloption[]" value="true" '.$checked.'></td></tr>';
    // Default value
    print '<tr><td class="field">'.$langs->trans("DefaultValue").'</td><td class="valeur"><input type="text" name="defaultvalue" size="10" value="'.$field_defaultvalue.'"></td></tr>';

    // SQL constraints
    print '<tr><td class="field">'.$langs->trans("Constraint").'</td><td class="valeur">';
    $tables = $customfields->fetchAllTables();
    $tables = array_merge(array('' => $langs->trans('None')), $tables); // Adding a none choice (to avoid choosing a constraint or just to delete one)
    print $html->selectarray('constraint',$tables,$field_constraint);
    print '</td></tr>';

    // Custom SQL
    print '<tr><td class="field">'.$langs->trans("CustomSQLDefinition").' ('.$langs->trans("CustomSQLDefinitionDesc").')</td><td class="valeur"><input type="text" name="customdef" size="50" value="'.GETPOST('customdef').'"></td></tr>';
    print '<tr><td class="field">'.$langs->trans("CustomSQL").' ('.$langs->trans("CustomSQLDesc").')</td><td class="valeur"><input type="text" name="customsql" size="50" value="'.GETPOST('customsql').'"></td></tr>';

    print '<tr><td colspan="2" align="center"><input type="submit" name="button" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
    print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';
    print "</form>\n";
    print "</table>\n";
}

// some other necessary footer and db closing
$db->close();

llxFooter('$$');
// end of necessary footers
?>
