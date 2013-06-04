<?PHP

class OAIPMHServer extends Plugin {

    function Register()
    {
        $this->Name = "OAI-PMH Server";
        $this->Version = "1.0.0";
        $this->Description = "Provides support for"
                ." serving up resource records using version 2.0 of the <a"
                ." href=\"http://www.openarchives.org/OAI/openarchivesprotocol.html\""
                ." target=\"_blank\">Open Archives"
                ." Initiative Protocol for Metadata Harvesting</a> (OAI-PMH).";
        $this->Author = "Internet Scout";
        $this->Url = "http://scout.wisc.edu/cwis/";
        $this->Email = "scout@scout.wisc.edu";
        $this->Requires = array("CWISCore" => "2.1.0");
        $this->EnabledByDefault = TRUE;
    }

    function HookEvents()
    {
        return array(
                "EVENT_PHP_FILE_LOAD" => "CheckForOaiRequest",
                "EVENT_COLLECTION_ADMINISTRATION_MENU" => "AddCollectionAdminMenuItems",
                "EVENT_SYSTEM_INFO_LIST" => "AddSystemInfoListItems",
                );
    }

    function DeclareEvents()
    {
        return array(
                "OAIPMHServer_EVENT_MODIFY_RESOURCE_SEARCH_PARAMETERS"
                        => ApplicationFramework::EVENTTYPE_CHAIN,
                "OAIPMHServer_EVENT_FILTER_RESULTS"
                        => ApplicationFramework::EVENTTYPE_CHAIN,
                );
    }

    function AddCollectionAdminMenuItems()
    {
        return array("EditConfig" => "OAI Server Configuration");
    }

    function AddSystemInfoListItems()
    {
        $RepDescr = $this->ConfigSetting("RepositoryDescr");
        $ServerUrl = $RepDescr["BaseURL"];
        $OaiExplorerUrl = "http://re.cs.uct.ac.za/cgi-bin/Explorer/2.0-1.46/"
                ."testoai?metadataPrefix=oai_dc&archive=".urlencode($ServerUrl);
        return array("OAI-PMH Server Base URL" =>
                "<a href=\"".htmlspecialchars($ServerUrl)."\" target=\"_blank\">"
                        .htmlspecialchars($ServerUrl)."</a>"
                        ."&nbsp;&nbsp;&nbsp;&nbsp;"
                        ."<a class=\"cw-button cw-button-constrained cw-button-elegant\""
                        ." href=\""
                        .htmlspecialchars($OaiExplorerUrl)
                        ."\" target=\"_blank\">TEST</a>"
                );
    }

    function Install()
    {
        # set up configuration defaults
        global $G_SysConfig;
        $RepDescr["Name"] = $G_SysConfig->PortalName();
        $RepDescr["AdminEmail"] = array($G_SysConfig->AdminEmail());
        $ServerName = ($_SERVER["SERVER_NAME"] != "127.0.0.1")
                        ? $_SERVER["SERVER_NAME"]
                        : $_SERVER["HTTP_HOST"];
        $ServerName = str_replace('/','',$ServerName);
        $ServerName = ($ServerName == "localhost")
                ? gethostname() : $ServerName;
        $RepDescr["BaseURL"] = "http://".$ServerName.$_SERVER["SCRIPT_NAME"];
        $RepDescr["IDDomain"] = $ServerName;
        $RepDescr["IDPrefix"] = $ServerName;
        $RepDescr["DateGranularity"] = "DATE";
        $RepDescr["EarliestDate"] = "1990-01-01";
        $this->ConfigSetting("RepositoryDescr", $RepDescr);
        $this->ConfigSetting("SQEnabled", TRUE);

        # copy over old configuration info (if any)
        $this->TransferLegacyConfiguration();

        # look for format outline files in plugin directory
        $this->FormatFileLocation = dirname(__FILE__);

        # initialize/expand formats from format outlines
        $this->LoadFormatsFromOutlines();

        # build and add native format
        $NativeFormatSuffix = substr(strtolower(preg_replace(
                "/[^a-zA-Z0-9]/", "", $RepDescr["Name"])), 0, 8);
        $this->AddNativeFormat($NativeFormatSuffix, $RepDescr["BaseURL"]);

        # report installation error if no oai_dc format found
        $Formats = $this->ConfigSetting("Formats");
        if (!isset($Formats["oai_dc"]))
        {
            return "Required oai_dc format not found.";
        }
    }

    /**
    * Check GET parameters for OAI-PMH request and redirect to our OAI server
    * page if found.  (HOOKED to EVENT_PHP_FILE_LOAD)
    * @param PageName PHP file name.
    * @return PHP file name, possibly changed to load our page.
    */
    function CheckForOaiRequest($PageName)
    {
        $OAIRequiredArguments = array(
                "GetRecord" => array(
                        "identifier",
                        "metadataPrefix",
                        ),
                "Identify" => array(),
                "ListIdentifiers" => array(),
                "ListMetadataFormats" => array(),
                "ListRecords" => array(),
                "ListSets" => array(),
                );

        if ((!isset($_GET["P"]) || ($_GET["P"] == "OAI"))
                && (isset($_GET["verb"]) || isset($_POST["verb"]))
                && (isset($OAIRequiredArguments[$_GET["verb"]])
                        || isset($OAIRequiredArguments[$_POST["verb"]])))
        {
            $GoodRequest = TRUE;
            foreach ($OAIRequiredArguments[$_GET["verb"]] as $RequiredArgument)
            {
                if (!isset($_GET[$RequiredArgument]))
                {
                    $GoodRequest = FALSE;
                }
            }
            if ($GoodRequest)
            {
                $PageName = "P_OAIPMHServer_OAI.php";
            }
        }
        return array("PageName" => $PageName);
    }


    # ----- PRIVATE INTERFACE ------------------------------------------------

    private $FormatFileLocation;

    /**
    * Load XML format outline files.
    * @return Array containing format information.
    */
    private function LoadFormatOutlines()
    {
        # for all files in format file location
        $Formats = array();
        $FileList = scandir($this->FormatFileLocation);
        foreach ($FileList as $FileName)
        {
            # if file looks like a format file
            if (preg_match("/^Format--[a-zA-Z0-9_-]+\\.xml\$/i", $FileName))
            {
                # read in format from file
                $FullFileName = realpath($this->FormatFileLocation."/".$FileName);
                $Xml = simplexml_load_file($FullFileName);
                if (isset($Xml->formatName))
                {
                    $Formats[(string)$Xml->formatName] = SimpleXMLToArray($Xml);
                }
            }
        }

        # return loaded formats to caller
        return $Formats;
    }

    /**
    * Transfer over legacy OAI-PMH server configuration.
    */
    private function TransferLegacyConfiguration()
    {
        # if old OAI-PMH configuration is available
        $DB = new Database();
        if ($DB->FieldExists("SystemConfiguration", "OaiIdDomain"))
        {
            # copy base configuration from legacy OAI-PMH server support values
            $RepDescr = $this->ConfigSetting("RepositoryDescr");
            global $G_SysConfig;
            if (strlen(trim($G_SysConfig->OaiIdDomain())))
                    $RepDescr["IDDomain"] = $G_SysConfig->OaiIdDomain();
            if (strlen(trim($G_SysConfig->OaiIdPrefix())))
                    $RepDescr["IDPrefix"] = $G_SysConfig->OaiIdPrefix();
            if (($G_SysConfig->OaiDateGranularity() == "DATE")
                    || ($G_SysConfig->OaiDateGranularity() == "DATETIME"))
                    $RepDescr["DateGranularity"] = $G_SysConfig->OaiDateGranularity();
            if (strlen(trim($G_SysConfig->OaiEarliestDate())))
                    $RepDescr["EarliestDate"] = $G_SysConfig->OaiEarliestDate();
            $this->ConfigSetting("RepositoryDescr", $RepDescr);
            $this->ConfigSetting("SQEnabled", $G_SysConfig->OAISQEnabled());

            # copy existing field mappings
            $DB->Query("SELECT * FROM OAIFieldMappings");
            while ($Record = $DB->FetchRow())
            {
                if ($Record["OAIFieldName"] != "Unmapped")
                {
                    $Formats[$Record["FormatName"]]
                            ["Elements"][$Record["OAIFieldName"]]
                            = $Record["SPTFieldId"];
                }
            }

            # copy existing qualifier mappings
            $DB->Query("SELECT * FROM OAIQualifierMappings");
            while ($Record = $DB->FetchRow())
            {
                if ($Record["OAIQualifierName"] != "Unmapped")
                {
                    $Formats[$Record["FormatName"]]
                            ["Qualifiers"][$Record["OAIQualifierName"]]
                            = $Record["SPTQualifierId"];
                }
            }
            $this->ConfigSetting("Formats", $Formats);
        }
    }

    /**
    * Load new formats from format outline files.
    */
    private function LoadFormatsFromOutlines()
    {
        # load current formats
        $Formats = $this->ConfigSetting("Formats");

        # load format outlines from files
        $FormatOutlines = $this->LoadFormatOutlines();

        # for each loaded format outline
        foreach ($FormatOutlines as $FormatName => $Outline)
        {
            # save any needed basic format info
            if (!isset($Formats[$FormatName]["TagName"]))
                    $Formats[$FormatName]["TagName"] = $Outline["tagName"];
            if (!isset($Formats[$FormatName]["SchemaNamespace"]))
                    $Formats[$FormatName]["SchemaNamespace"]
                            = $Outline["schema"]["namespace"];
            if (!isset($Formats[$FormatName]["SchemaDefinition"]))
                    $Formats[$FormatName]["SchemaDefinition"]
                            = $Outline["schema"]["definition"];
            if (!isset($Formats[$FormatName]["SchemaVersion"])
                    && is_string($Outline["schema"]["version"]))
                    $Formats[$FormatName]["SchemaVersion"]
                            = $Outline["schema"]["version"];

            # if there are no namespaces set for this format
            if (!isset($Formats[$FormatName]["Namespaces"])
                    || !count($Formats[$FormatName]["Namespaces"]))
            {
                # if there are namespaces for this format outline
                $Formats[$FormatName]["Namespaces"] = array();
                if (isset($Outline["namespace"]))
                {
                    # convert namespace to array if necessary
                    if (!isset($Outline["namespace"][0]))
                    {
                        $Outline["namespace"] = array($Outline["namespace"]);
                    }

                    # for each namespace
                    foreach ($Outline["namespace"] as $Namespace)
                    {
                        # if mapping looks viable
                        if (isset($Namespace["name"]) && strlen($Namespace["name"])
                                && isset($Namespace["uri"])
                                && strlen($Namespace["uri"]))
                        {
                            # map namespace
                            $Formats[$FormatName]["Namespaces"][$Namespace["name"]]
                                    = $Namespace["uri"];
                        }
                    }
                }
            }

            # if there are no elements set for this format
            if (!isset($Formats[$FormatName]["Elements"])
                    || !count($Formats[$FormatName]["Elements"]))
            {
                # if there are element mappings for this format outline
                $Formats[$FormatName]["Elements"] = array();
                if (isset($Outline["element"]))
                {
                    # convert element mapping to array if necessary
                    if (!isset($Outline["element"][0]))
                    {
                        $Outline["element"] = array($Outline["element"]);
                    }

                    # for each element mapping
                    $Schema = new MetadataSchema();
                    foreach ($Outline["element"] as $Element)
                    {
                        # if mapping looks viable
                        if (isset($Element["name"]) && strlen($Element["name"]))
                        {
                            # map element
                            $Formats[$FormatName]["Elements"][$Element["name"]]
                                    = $this->MapOutlineName($Element, $Schema, TRUE);
                        }
                    }
                }
            }

            # if there are no qualifiers set for this format
            if (!isset($Formats[$FormatName]["Qualifiers"])
                    || !count($Formats[$FormatName]["Qualifiers"]))
            {
                # if there are qualifier mappings for this format outline
                $Formats[$FormatName]["Qualifiers"] = array();
                if (isset($Outline["qualifier"]))
                {
                    # convert qualifier mapping to array if necessary
                    if (!isset($Outline["qualifier"][0]))
                    {
                        $Outline["qualifier"] = array($Outline["qualifier"]);
                    }

                    # for each qualifier mapping
                    $QFactory = new QualifierFactory();
                    foreach ($Outline["qualifier"] as $Qualifier)
                    {
                        # if mapping looks viable
                        if (isset($Qualifier["name"]) && strlen($Qualifier["name"]))
                        {
                            # map qualifier
                            $Formats[$FormatName]["Qualifiers"][$Qualifier["name"]]
                                    = $this->MapOutlineName($Element, $QFactory);
                        }
                    }
                }
            }
        }

        # save updated formats
        $this->ConfigSetting("Formats", $Formats);
    }

    /**
    * Add format built off of native schema.
    */
    private function AddNativeFormat($FormatNameSuffix, $BaseUrl)
    {
        # set up format description
        $FormatNameSuffix = trim($FormatNameSuffix);
        if (!strlen($FormatNameSuffix)) {  $FormatNameSuffix = "xxx";  }
        $Format["FormatName"] = "native_".$FormatNameSuffix;
        $Format["TagName"] = $FormatNameSuffix;
        $Format["SchemaNamespace"] = $BaseUrl."/".$FormatNameSuffix;
        $Format["SchemaDefinition"] = $BaseUrl."/"."XSD/file/path/goes/here.xsd";
        $Format["SchemaVersion"] = "1.0.0";

        # swipe namespaces and qualifiers from nsdl_dc format
        $Formats = $this->ConfigSetting("Formats");
        $Format["Namespaces"] = $Formats["nsdl_dc"]["Namespaces"];
        $Format["Qualifiers"] = $Formats["nsdl_dc"]["Qualifiers"];

        # for each currently enabled metadata field
        $Format["Elements"] = array();
        $Schema = new MetadataSchema();
        $Fields = $Schema->GetFields();
        foreach ($Fields as $FieldId => $Field)
        {
            # normalize metadata field name to create OAI element name
            $ElementName = preg_replace("/[^a-zA-Z0-9]/", "", $Field->Name());
            $ElementName{0} = strtolower($ElementName{0});
            $ElementName = $ElementName;

            # add element mapping to format
            $Format["Elements"][$ElementName] = $FieldId;
        }

        # save new format
        $Formats[$Format["FormatName"]] = $Format;
        $this->ConfigSetting("Formats", $Formats);
    }

    /**
    * Map recommended field name (from format outline file) to metadata field ID.
    * @param Element Associative array with element info.
    * @param Factory ItemFactory-derived factory object to query for mappable items.
    * @param LookForStdName Call factory method to search for standard mappings.
    * @return Metadata field ID or -1 if no appropriate field found.
    */
    private function MapOutlineName($Element, $Factory, $LookForStdName = FALSE)
    {
        # return -1 if no mapping found
        $FieldId = -1;

        # if recommended mapping was supplied
        if (isset($Element["recommendedMapping"]))
        {
            # build list of recommended mappings
            $Recommendations = array($Element["recommendedMapping"]);
            if (isset($Element["alternateMapping"]))
            {
                if (is_array($Element["alternateMapping"]))
                {
                    $Recommendations = array_merge($Recommendations,
                            $Element["alternateMapping"]);
                }
                else
                {
                    $Recommendations[] = $Element["alternateMapping"];
                }
            }

            # for each recommended mapping
            foreach ($Recommendations as $Name)
            {
                # look for field with supplied name
                $FieldId = $Factory->GetItemByName($Name, TRUE);

                # if field found
                if ($FieldId !== FALSE)
                {
                    # stop searching
                    break;
                }
                elseif ($LookForStdName)
                {
                    # look for field mapped to supplied standard name
                    $FieldId = $Schema->StdNameToFieldMapping($Name);

                    # if field mapped to supplied standard name found
                    if ($FieldId !== NULL)
                    {
                        # stop searching
                        break;
                    }
                }

                # no mapping found so restore -1 field ID to indicate none found
                $FieldId = -1;
            }
        }

        # return ID of mapped field to caller
        return $FieldId;
    }
}


?>
