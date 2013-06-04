<?PHP

header("Content-type: text/xml");

$Plugin = $GLOBALS["G_PluginManager"]->GetPlugin("OAIPMHServer");
$RetrievalSearch = function_exists("Local_GetOAIRetrievalSearchParameters")
        ? Local_GetOAIRetrievalSearchParameters() : NULL;
$Server = new POAIServer($Plugin->ConfigSetting("RepositoryDescr"),
        $Plugin->ConfigSetting("Formats"), $RetrievalSearch,
        $Plugin->ConfigSetting("SQEnabled"));

# log OAI request
$EventLog = new SPTEventLog();
$EventLog->Log(SptEventLog::SPTEVENT_OAIHARVEST,
        $_SERVER["REMOTE_ADDR"], $_SERVER["QUERY_STRING"]);

# signal OAI-PMH request event (stripping P= page parameter out of GET string)
$QueryString = preg_replace(
        array("/&P=[a-zA-Z0-9_-]+[&]*/", "/^P=[a-zA-Z0-9_-]+[&]*/"),
        array("&", ""), $_SERVER["QUERY_STRING"]);
$AF->SignalEvent("EVENT_OAIPMH_REQUEST", array(
        "RequesterIP" => $_SERVER["REMOTE_ADDR"],
        "QueryString" => $QueryString));

$ServerResponse = $Server->GetResponse();

if (isset($_GET["metadataPrefix"]) || isset($_POST["metadataPrefix"]))
{
    $SelectedFormat = isset($_GET["metadataPrefix"]) ?
        $_GET["metadataPrefix"] :
        $_POST["metadataPrefix"] ;
}
elseif (isset($_GET["resumptionToken"]) || isset($_POST["resumptionToken"]))
{
    $ResumptionToken = isset($_GET["resumptionToken"]) ?
        $_GET["resumptionToken"] :
        $_POST["resumptionToken"] ;
    $Pieces = preg_split("/-_-/", $ResumptionToken );
    if (count($Pieces)==5 && strlen($Pieces[2])>0 )
    {
        $SelectedFormat = $Pieces[2];
    }
}

$Formats = $Plugin->ConfigSetting("Formats");

if (isset($Formats[$SelectedFormat]) &&
    isset($Formats[$SelectedFormat]["XsltFileId"]))
{
    $xml = new DOMDocument;
    $xml->loadXML($ServerResponse);

    $XslFile = new File( intval($Formats[$SelectedFormat]["XsltFileId"]) );

    $xsl = new DOMDocument;
    $xsl->load( $XslFile->GetNameOfStoredFile() );

    $proc = new XSLTProcessor;
    $proc->importStyleSheet($xsl);

    print ($proc->transformToXML($xml) );
}
else
{
    print ($ServerResponse);
}

# suppress any HTML output
$AF->SuppressHTMLOutput();

?>
