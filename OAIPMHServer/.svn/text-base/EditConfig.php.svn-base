<?PHP

if (!CheckAuthorization(PRIV_COLLECTIONADMIN, PRIV_SYSADMIN)) {  return;  }

$Plugin = $GLOBALS["G_PluginManager"]->GetPlugin("OAIPMHServer");

# check for format edit button click
$Formats = $Plugin->ConfigSetting("Formats");
$Index = 0;
$FormatToEdit = "";
foreach ($Formats as $FormatName => $Format)
{
    if (isset($_POST["FormatEdit".$Index]))
    {
        $FormatToEdit = $_POST["H_FormatName".$Index];
        $_POST["Submit"] = "Edit";
        break;
    }
    $Index++;
}

# if user clicked a button
if (isset($_POST["Submit"]))
{
    # if user did not click "Cancel"
    if ($_POST["Submit"] != "Cancel")
    {
        # check for required values
        $FormVars = array(
                "Name" => "Repository Name",
                "BaseURL" => "Base URL",
                "AdminEmail" => "Administrator Email",
                "IDDomain" => "ID Domain",
                "IDPrefix" => "ID Prefix",
                "EarliestDate" => "Earliest Date",
                "DateGranularity" => "Date Granularity",
                );
        foreach ($FormVars as $FieldName => $PrintableName)
        {
            if (!strlen(trim($_POST["F_RepDescr_".$FieldName])))
            {
                $G_ErrorMessages[] = "<i>".$PrintableName."</i> is required.";
            }
            else
            {
                if ($FieldName == "AdminEmail")
                    $RepDescr[$FieldName][] = trim($_POST["F_RepDescr_".$FieldName]);
                else
                    $RepDescr[$FieldName] = trim($_POST["F_RepDescr_".$FieldName]);
            }
        }
    
        # if no errors found
        if (!isset($G_ErrorMessages))
        {
            # save configuration
            $Plugin->ConfigSetting("RepositoryDescr", $RepDescr);
            $Plugin->ConfigSetting("SQEnabled", $_POST["F_SQEnabled"]);
        }
        else
        {
            # reload values for use in HTML
            $G_RepDescr = $RepDescr;
            $G_Formats = $Plugin->ConfigSetting("Formats");
            $G_SQEnabled = $_POST["F_SQEnabled"];
        }
    }

    # take action based on which button was clicked
    switch ($_POST["Submit"])
    {
        case "Save Changes":
            # if no errors were found with values
            if (!isset($G_ErrorMessages))
            {
                # head back to sys admin page
                $AF->SetJumpToPage("SysAdmin");
            }
            break;

        case "Cancel":
            # head back to sys admin page
            $AF->SetJumpToPage("SysAdmin");
            break;

        case "Edit":
        case "Add Format":
            # head to format editing page
            $AF->SetJumpToPage("P_OAIPMHServer_EditFormat&amp;FN=".$FormatToEdit);
            break;
    }
}
# coming into page from elsewhere
else
{
    # load values for use in HTML
    $G_RepDescr = $Plugin->ConfigSetting("RepositoryDescr");
    $G_Formats = $Plugin->ConfigSetting("Formats");
    $G_SQEnabled = $Plugin->ConfigSetting("SQEnabled");
}

?>
