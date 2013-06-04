<?PHP

class POAIItemFactory implements OAIItemFactory {

    # ---- PUBLIC INTERFACE --------------------------------------------------

    # object constructor
    function POAIItemFactory($RetrievalSearchParameters = NULL)
    {
        # save any supplied retrieval parameters
        $this->RetrievalSearchParameters = $RetrievalSearchParameters;
    }

    function GetItem($ItemId)
    {
        # add link to full record page for item
        $ServerName = ($_SERVER["SERVER_NAME"] != "127.0.0.1")
                ? $_SERVER["SERVER_NAME"]
                : $_SERVER["HTTP_HOST"];
        $ServerName = str_replace('/','',$ServerName);
        $SearchInfo["fullRecordLink"] =
            "http://".$ServerName.dirname($_SERVER["SCRIPT_NAME"])
            ."/index.php?P=FullRecord&ID=".$ItemId;

        # if a search score is available for the item
        if (isset($this->SearchScores) && isset($this->SearchScores[$ItemId]))
        {
            # add search info for item
            $SearchInfo["searchScore"] = $this->SearchScores[$ItemId];
            $SearchInfo["searchScoreScale"] = $this->SearchScoreScale;
        }

        # attempt to create item
        $Item = new POAIItem($ItemId, $SearchInfo);

        # if item creation failed
        if ($Item->Status() == -1)
        {
            # return NULL to indicate that no item was found with that ID
            return NULL;
        }
        else
        {
            # return item to caller
            return $Item;
        }
    }

    function GetItems($StartingDate = NULL, $EndingDate = NULL)
    {
        return $this->GetItemsInSet(NULL, $StartingDate, $EndingDate);
    }

    function GetItemsInSet($Set,
            $StartingDate = NULL, $EndingDate = NULL, $SearchStrings = NULL)
    {
        # add release flag requirement
        $SearchStrings["Release Flag"] = "=1";

        # if both begin and end date supplied
        if (($StartingDate != NULL) && ($EndingDate != NULL))
        {
            # select resources created between starting and ending dates
            $SearchStrings["Date Of Record Creation"] =
                    array(">=".$StartingDate, "<=".$EndingDate);
        }
        # else if begin date specified
        elseif ($StartingDate != NULL)
        {
            # select resources created after begin date
            $SearchStrings["Date Of Record Creation"] = ">=".$StartingDate;
        }
        # else if end date specified
        elseif ($EndingDate != NULL)
        {
            # select resources created after begin date
            $SearchStrings["Date Of Record Creation"] = "<=".$EndingDate;
        }

        # if set specified
        if ($Set != NULL)
        {
            # load set mappings
            $this->LoadSetNameInfo();

            # if set is valid
            if (isset($this->SetFields[$Set]))
            {
                # add field spec to search strings
                $SearchStrings[$this->SetFields[$Set]] = "= ".$this->SetValues[$Set];
            }
            else
            {
                # set will not match anything so return empty array to caller
                return array();
            }
        }

        # set up search parameter groups
        if ($this->RetrievalSearchParameters)
        {
            $SearchStrings = array_merge($SearchStrings,
                    $this->RetrievalSearchParameters);
        }
        $SearchGroups["MAIN"] = array(
                "Logic" => SearchEngine::LOGIC_AND,
                "SearchStrings" => $SearchStrings);

        # allow any hooked handlers to modify search parameters if desired
        $SignalResult = $GLOBALS["AF"]->SignalEvent(
                "OAIPMHServer_EVENT_MODIFY_RESOURCE_SEARCH_PARAMETERS",
                array("SearchGroups" => $SearchGroups));
        if (isset($SignalResult["SearchGroups"]))
        {
            $SearchGroups = $SignalResult["SearchGroups"];
        }

        # perform search for desired items
        $Engine = new SPTSearchEngine();
        $SearchResults = $Engine->GroupedSearch($SearchGroups, 0, 1000000);

        # save search scores
        $this->SearchScores = $SearchResults;
        $this->SearchScoreScale = $Engine->FieldedSearchWeightScale($SearchStrings);

        # extract resource IDs from search results
        $ItemIds = array_keys($SearchResults);

        # allow any hooked handlers to filter results if desired
        $SignalResult = $GLOBALS["AF"]->SignalEvent(
                "OAIPMHServer_EVENT_FILTER_RESULTS",
                array("ItemIds" => $ItemIds));
        if (isset($SignalResult["ItemIds"]))
        {
            $ItemIds = $SignalResult["ItemIds"];
        }

        # return array of resource IDs to caller
        return $ItemIds;
    }

    # retrieve IDs of items that match search parameters (only needed if OAI-SQ supported)
    function SearchForItems($SearchParams, $StartingDate = NULL, $EndingDate = NULL)
    {
        # translate field IDs into field names for search parameters
        $Schema = new MetadataSchema;
        foreach ($SearchParams as $FieldId => $Value)
        {
            if ($FieldId == "X-KEYWORD-X")
            {
                $SearchStrings["XXXKeywordXXX"] = $Value;
            }
            else
            {
                $Field = $Schema->GetField($FieldId);
                $SearchStrings[$Field->Name()] = $Value;
            }
        }

        # perform search and return results to caller
        return $this->GetItemsInSet(NULL, $StartingDate, $EndingDate, $SearchStrings);
    }

    # return array containing all set specs (with human-readable set names as keys)
    function GetListOfSets()
    {
        # make sure set name info is loaded
        $this->LoadSetNameInfo();

        # return list of sets to caller
        return $this->SetSpecs;
    }


    # ---- PRIVATE INTERFACE -------------------------------------------------

    private $SetSpecs;
    private $SetFields;
    private $SetValues;
    private $RetrievalSearchParameters;
    private $SearchScores;
    private $SearchScoreScale;

    # normalize value for use as an OAI set spec
    private function NormalizeForSetSpec($Name)
    {
        return preg_replace("/[^a-zA-Z0-9\-_.!~*'()]/", "", $Name);
    }

    # load normalized set names and name mappings
    private function LoadSetNameInfo()
    {
        # if set names have not already been loaded
        if (!isset($this->SetSpecs))
        {
            # start with empty list of sets
            $this->SetSpecs = array();
            $this->SetFields = array();
            $this->SetValues = array();

            # for each metadata field that is a type that can be used for sets
            $Schema = new MetadataSchema();
            $Fields = $Schema->GetFields(MetadataSchema::MDFTYPE_TREE
                    |MetadataSchema::MDFTYPE_CONTROLLEDNAME
                    |MetadataSchema::MDFTYPE_OPTION);
            foreach ($Fields as $Field)
            {
                # if field is flagged as being used for OAI sets
                if ($Field->UseForOaiSets())
                {
                    # retrieve all possible values for field
                    $FieldValues = $Field->GetPossibleValues();

                    # prepend field name to each value and add to list of sets
                    $FieldName = $Field->Name();
                    $NormalizedFieldName = $this->NormalizeForSetSpec($FieldName);
                    foreach ($FieldValues as $Value)
                    {
                        $SetSpec = $NormalizedFieldName.":"
                                .$this->NormalizeForSetSpec($Value);
                        $this->SetSpecs[$FieldName.": ".$Value] = $SetSpec;
                        $this->SetFields[$SetSpec] = $FieldName;
                        $this->SetValues[$SetSpec] = $Value;
                    }
                }
            }
        }
    }
}

?>
