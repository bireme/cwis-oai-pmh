<?PHP

class POAIServer extends OAIServer {

    # ---- PUBLIC INTERFACE --------------------------------------------------

    function POAIServer($RepDescr, $Formats, $RetrievalSearch, $OAISQEnabled)
    {
        global $SysConfig;

        # grab our own database handle
        $this->DB = new Database();
        $DB =& $this->DB;

        # create item factory object for retrieving items from DB
        $this->PItemFactory = new POAIItemFactory($RetrievalSearch);

        # call parent's constructor
        $this->OAIServer($RepDescr, $this->PItemFactory, TRUE, $OAISQEnabled);

        # for each defined format
        foreach ($Formats as $FormatName => $Format)
        {
            # add format to supported list
            $this->AddFormat($FormatName, $Format["TagName"], 
                    (isset($Format["SchemaNamespace"]) 
                            ? $Format["SchemaNamespace"] : NULL), 
                    (isset($Format["SchemaDefinition"]) 
                            ? $Format["SchemaDefinition"] : NULL), 
                    (isset($Format["SchemaVersion"]) 
                            ? $Format["SchemaVersion"] : NULL), 
                    $Format["Namespaces"], 
                    array_keys($Format["Elements"]), 
                    array_keys($Format["Qualifiers"]));

            # set element mappings
            foreach ($Format["Elements"] as $ElementName => $FieldId)
            {
                if ($FieldId != -1)
                {
                    parent::SetFieldMapping($FormatName, $FieldId, $ElementName);
                }
            }

            # set qualifier mappings
            foreach ($Format["Qualifiers"] as $OAIQualifierName => $QualifierId)
            {
                if ($QualifierId >= 0)
                {
                    $Qualifier = new Qualifier($QualifierId);
                    parent::SetQualifierMapping(
                            $FormatName, $Qualifier->Name(), $OAIQualifierName);
                }
            }
        }
    }

    # add SQL conditional for selecting resources
    function AddSQLConditionalForResources($Conditional)
    {
        # pass conditional on to item factory
        $this->PItemFactory->AddSQLConditionalForResources($Conditional);
    }

    # get/set mapping of local field to OAI field (overloads parent method)
    function GetFieldMapping($FormatName, $LocalFieldName)
    {
        # retrieve ID for local field
        $Schema = new MetadataSchema();
        $LocalField = $Schema->GetFieldByName($LocalFieldName);
        $LocalFieldId = $LocalField->Id();

        # return stored value
        return parent::GetFieldMapping($FormatName, $LocalFieldId);
    }
    function SetFieldMapping($FormatName, $LocalFieldName, $OAIFieldName)
    {
        # retrieve ID for local field
        $Schema = new MetadataSchema();
        $LocalField = $Schema->GetFieldByName($LocalFieldName);
        $LocalFieldId = $LocalField->Id();

        # check whether mapping is already in database
        $DB =& $this->DB;
        $MapCount = $DB->Query("SELECT COUNT(*) AS MapCount FROM OAIFieldMappings"
                               ." WHERE FormatName = '".$FormatName."'"
                               ." AND SPTFieldId = '".$LocalFieldId."'",
                               "MapCount");

        # if mapping is already in database
        if ($MapCount > 0)
        {
            # change mapping in database
            $DB->Query("UPDATE OAIFieldMappings"
                       ." SET OAIFieldName = '".addslashes($OAIFieldName)."'"
                       ." WHERE FormatName = '".addslashes($FormatName)."'"
                       ." AND SPTFieldId = '".$LocalFieldId."'");
        }
        else
        {
            # add new mapping to database
            $DB->Query("INSERT INTO OAIFieldMappings"
                       ." (FormatName, SPTFieldId, OAIFieldName) VALUES"
                       ." ('".addslashes($FormatName)."', '".$LocalFieldId
                       ."', '".addslashes($OAIFieldName)."')");
        }

        # call parent method
        parent::SetFieldMapping($FormatName, $LocalFieldId, $OAIFieldName);
    }

    # set mapping of local qualifier to OAI qualifier (overloads parent method)
    function SetQualifierMapping($FormatName, $LocalQualifierName, $OAIQualifierName)
    {
        # retrieve ID for local qualifier
        $QFactory = new QualifierFactory();
        $LocalQualifier = $QFactory->GetQualifierByName($LocalQualifierName);
        $LocalQualifierId = $LocalQualifier->Id();

        # check whether mapping is already in database
        $DB =& $this->DB;
        $MapCount = $DB->Query("SELECT COUNT(*) AS MapCount FROM OAIQualifierMappings"
                               ." WHERE FormatName = '".addslashes($FormatName)."'"
                               ." AND SPTQualifierId = '".$LocalQualifierId."'",
                               "MapCount");

        # if mapping is already in database
        if ($MapCount > 0)
        {
            # change mapping in database
            $DB->Query("UPDATE OAIQualifierMappings"
                       ." SET OAIQualifierName = '".addslashes($OAIQualifierName)."'"
                       ." WHERE FormatName = '".addslashes($FormatName)."'"
                       ." AND SPTQualifierId = '".$LocalQualifierId."'");
        }
        else
        {
            # add new mapping to database
            $DB->Query("INSERT INTO OAIQualifierMappings"
                       ." (FormatName, SPTQualifierId, OAIQualifierName) VALUES"
                       ." ('".addslashes($FormatName)."', '".$LocalQualifierId
                       ."', '".addslashes($OAIQualifierName)."')");
        }

        # call parent method
        parent::SetQualifierMapping($FormatName, $LocalQualifierName, $OAIQualifierName);
    }


    # ---- PRIVATE INTERFACE -------------------------------------------------

    var $DB;
    var $PItemFactory;

}

?>
