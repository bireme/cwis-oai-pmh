<?PHP

if (!CheckAuthorization(PRIV_COLLECTIONADMIN)) {  return;  }

function SaveChanges($Format, $FormatName)
{
    # load existing formats
    $Plugin = $GLOBALS["G_PluginManager"]->GetPlugin("OAIPMHServer");
    $Formats = $Plugin->ConfigSetting("Formats");

    # if format name changed
    $OldFormatName = $_POST["H_FormatName"];
    if (strlen($OldFormatName) && ($Format["FormatName"] != $OldFormatName))
    {
        # clear format under old name
        unset($Formats[$OldFormatName]);

        # set new format name to save under
        $FormatName = $Format["FormatName"];
    }

    # clean up format item ordering
    if (!isset($Format["Namespaces"])) {  $Format["Namespaces"] = array();  }
    ksort($Format["Namespaces"]);
    if (!isset($Format["Elements"])) {  $Format["Elements"] = array();  }
    ksort($Format["Elements"]);
    if (!isset($Format["Qualifiers"])) {  $Format["Qualifiers"] = array();  }
    ksort($Format["Qualifiers"]);

    # save format
    $Formats[$FormatName] = $Format;
    $Plugin->ConfigSetting("Formats", $Formats);
}

# if we are coming in from format editing form
if (isset($_POST["H_FormatName"]))
{
    # retrieve format name
    $FormatName = $_POST["H_FormatName"];

    # check for "Delete" button click
    $ItemTypes = array("Namespace", "Element", "Qualifier");
    foreach ($ItemTypes as $ItemType)
    {
        $Index = 0;
        while (isset($_POST["F_".$ItemType."Name".$Index]))
        {
            if (isset($_POST["Delete".$ItemType.$Index]))
            {
                $ItemTypeToDelete = $ItemType;
                $ItemToDelete = $_POST["H_".$ItemType."Index".$Index];
                $_POST["Submit"] = "Delete Item";
                break 2;
            }
            $Index++;
        }
    }

    if (isset($_POST["DeleteXsltFile"]))
    {
       $_POST["Submit"] = "Delete File";
    }

    # check for required values
    $FormVars = array(
            "FormatName" => "Format Name",
            "TagName" => "Tag Name",
            "SchemaNamespace" => "Schema Namespace URI",
            "SchemaDefinition" => "Schema Definition URL",
            );
    foreach ($FormVars as $FieldName => $PrintableName)
    {
        if (!strlen(trim($_POST["F_".$FieldName])))
        {
            $G_ErrorMessages[] = $PrintableName." is required.";
        }
        else
        {
            $Format[$FieldName] = trim($_POST["F_".$FieldName]);
        }
    }

    # transfer optional values
    $FormVars = array(
            "SchemaVersion" => "Schema Version",
            );
    foreach ($FormVars as $FieldName => $PrintableName)
    {
        $Format[$FieldName] = trim($_POST["F_".$FieldName]);
    }

    if (isset($_POST["H_XsltFileId"]))
    {
        $Format["XsltFileId"] = trim($_POST["H_XsltFileId"]);
    }

    # for each item type (namespace/element/qualifier)
    foreach ($ItemTypes as $ItemType)
    {
        # for each old item
        $Index = 0;
        while (isset($_POST["F_".$ItemType."Name".$Index]))
        {
            # retrieve values from form
            $OrigIndex = $_POST["H_".$ItemType."Index".$Index];
            $NewIndex = trim($_POST["F_".$ItemType."Name".$Index]);
            $VFName = ($ItemType == "Namespace") ? "Url" : "Mapping";
            $NewValue = trim($_POST["F_".$ItemType.$VFName.$Index]);

            # if index value has changed
            if ($NewIndex != $OrigIndex)
            {
                # if new index value is blank
                if (!strlen($NewIndex) && strlen($NewValue) && ($NewValue != -1))
                {
                    # restore index value and warn user
                    $NewIndex = $OrigIndex;
                    $G_ErrorMessages[] = "Blank ".strtolower($ItemType)
                            ."  names are not allowed."
                            ." (Reverted to previous value)";
                }
                # else if new index value is a duplicate
                elseif (isset($Format[$ItemType."s"][$NewIndex]))
                {
                    # restore index value and warn user
                    $NewIndex = $OrigIndex;
                    $G_ErrorMessages[] = "Duplicate ".strtolower($ItemType)
                            ." names are not allowed."
                            ." (Reverted to previous value)";
                }
                else
                {
                    # unset value at location of old index
                    if (isset($Format[$ItemType."s"][$OrigIndex]))
                            unset($Format[$ItemType."s"][$OrigIndex]);
                }
            }

            # if index is blank and value is blank/unselected
            if (!strlen($NewIndex) && (!strlen($NewValue) || ($NewValue == -1)))
            {
                # delete item
                if (isset($Format[$ItemType."s"][$NewIndex]))
                        unset($Format[$ItemType."s"][$NewIndex]);
            }
            else
            {
                # save new item value
                $Format[$ItemType."s"][$NewIndex] = $NewValue;
            }
            $Index++;
        }

        # if there were new items
        $ItemCount = $_POST["H_".$ItemType."Count"];
        if ($Index < $ItemCount)
        {
            # for each new item
            for ($Index--;  $Index <= $ItemCount;  $Index++)
            {
                # retrieve new values from form
                $NewIndex = trim($_POST["F_".$ItemType."Name".$Index]);
                $VFName = ($ItemType == "Namespace") ? "Url" : "Mapping";
                $NewValue = trim($_POST["F_".$ItemType.$VFName.$Index]);

                # if new index value is blank
                if (!strlen($NewIndex) && strlen($NewValue) && ($NewValue != -1))
                {
                    # warn user
                    $G_ErrorMessages[] = "Blank ".strtolower($ItemType)
                            ."  names are not allowed.";
                }
                # else if new index value is a duplicate
                elseif (isset($Format[$ItemType."s"][$NewIndex]))
                {
                    # clear index value and warn user
                    $NewIndex = "";
                    $G_ErrorMessages[] = "Duplicate ".strtolower($ItemType)
                            ." names are not allowed.";
                }

                # if new index and value look valid
                if (strlen($NewIndex) && strlen($NewValue) && ($NewValue != -1))
                {
                    # save new item value
                    $Format[$ItemType."s"][$NewIndex] = $NewValue;
                }
            }
        }
    }

    # if user requested to save changes to format and no errors found
    if (($_POST["Submit"] == "Save Changes") && !isset($G_ErrorMessages))
    {
        SaveChanges($Format, $FormatName);
    } else {
        die(print_r($FormatName));
    }


    # take action based on which button was clicked
    switch ($_POST["Submit"])
    {
        case "Cancel":
            # head back to configuration editing page
            $AF->SetJumpToPage("P_OAIPMHServer_EditConfig");
            break;

        case "Save Changes":
            # head back to configuration editing page only if no errors found
            if (!isset($G_ErrorMessages))
                    {  $AF->SetJumpToPage("P_OAIPMHServer_EditConfig");  }
            break;

        case "Add Namespace":
            # add blank namespace entry
            $Format["Namespaces"][" "] = " ";
            break;

        case "Add Element":
            # add blank element entry
            $Format["Elements"][" "] = -1;
            break;

        case "Add Qualifier":
            # add blank qualifier entry
            $Format["Qualifiers"][" "] = -1;
            break;

        case "Delete Format":
            break;

        case "Delete Item":
            break;

        case "Delete File":
            $XsltFile = new File(intval($Format["XsltFileId"]));
            $XsltFile->Delete();

            unset($Format["XsltFileId"]);
            SaveChanges($Format, $FormatName);
            break;

        case "Upload File":
            # XSLT Files:
            if (isset($_FILES["F_XsltFile"]["tmp_name"]))
            {
                if ( !(is_dir("tmp") && is_writeable("tmp")) )
                {
                    $G_ErrorMessages []= "tmp does not exist or is not writeable, ".
                        "contact the site administrator with this error.";
                }
                elseif ( !(is_dir("FileStorage") && is_writeable("FileStorage")) )
                {
                    $G_ErrorMessages []= "FileStorage does not exist or is not writeable, ".
                        "contact the site administrator with this error.";
                }
                else
                {
                    $TmpFileName = $_FILES["F_XsltFile"]["tmp_name"];
                    $NewFile = new File($TmpFileName, -1, -1,
                                        $_FILES["F_XsltFile"]["name"]);

                    # if file save failed
                    if ($NewFile->Status() != File::FILESTAT_OK)
                    {
                        # set error message and error out
                        switch ($NewFile->Status())
                        {
                        case File::FILESTAT_ZEROLENGTH:
                            $G_ErrorMessages []= "Uploaded file was zero length.";
                            break;

                        default:
                            $G_ErrorMessages []= "File upload error.";
                            break;
                        }
                    }
                    else
                    {
                        # Save worked
                        $Format["XsltFileId"] = $NewFile->Id();
                        SaveChanges($Format, $FormatName);
                    }

                    unlink($TmpFileName);
                }
            }
            break;
    }

    # load values for possible use in HTML
    if (isset($Format))
    {
        $G_Format = $Format;
    }
    else
    {
        $G_Format = array(
                "FormatName" => "",
                "TagName" => "",
                "SchemaNamespace" => "",
                "SchemaDefinition" => "",
                "SchemaVersion" => "",
                "Namespaces" => array(" " => ""),
                "Elements" => array("" => -1),
                "Qualifiers" => array("" => -1),
                );
    }
    $G_FormatName = $FormatName;
}
else
{
    # if new format requested
    if (isset($_GET["FN"]) && ($_GET["FN"] == ""))
    {
        # start with blank format
        $G_FormatName = "";
        $G_Format = array(
                "FormatName" => "",
                "TagName" => "",
                "SchemaNamespace" => "",
                "SchemaDefinition" => "",
                "SchemaVersion" => "",
                "Namespaces" => array(" " => ""),
                "Elements" => array("" => -1),
                "Qualifiers" => array("" => -1),
                );
    }
    else
    {
        # if specified format is available
        $Plugin = $GLOBALS["G_PluginManager"]->GetPlugin("OAIPMHServer");
        $Formats = $Plugin->ConfigSetting("Formats");
        $G_FormatName = $_GET["FN"];
        if (isset($Formats[$G_FormatName]))
        {
            # retrieve format to be edited
            $G_Format = $Formats[$G_FormatName];

            # make sure internal format name is set
            if (!isset($G_Format["FormatName"]))
                    {  $G_Format["FormatName"] = $G_FormatName;  }
        }
        else
        {
            # return to configuration editing page
            $AF->SetJumpToPage("P_OAIPMHServer_EditConfig");
        }
    }
}

# set flag indicating if standard format (some fields should not be modified)
$G_StdFormat = ($G_FormatName == "oai_dc") ? TRUE : FALSE;

?>
