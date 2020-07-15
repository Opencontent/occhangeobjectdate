<?php

class OCChangeObjectDate
{
    private $Keys;

    private $FunctionKeys;

    private $AllKeys;

    private $publish_class = '';

    private $publish_attribute = '';

    private $future_date_state_id;

    private $past_date_state_id;

    function __construct()
    {
        $this->Keys = array(
            'id',
            'version',
            'publish_class',
            'publish_attribute',
            'past_date_state_id',
            'future_date_state_id',
        );

        $this->FunctionKeys = array(
            'classAttributes' => 'class_attributes',
            'publishClassArray' => 'publish_class_array',
            'publishAttributeArray' => 'publish_attribute_array',
            'publishIDArray' => 'publish_id_array',
            'pastDateState' => 'past_date_state',
            'futureDateState' => 'future_date_state',
        );

        $this->AllKeys = array_merge($this->Keys, $this->FunctionKeys);
        sort($this->AllKeys);
    }

    static function create($id, $version, $publishClass, $publishAttribute, $pastDateState, $futureDateState)
    {
        $changeDate = new OCChangeObjectDate();
        $changeDate->setAttribute('id', $id);
        $changeDate->setAttribute('version', $version);

        $changeDate->setAttribute('publish_class', $publishClass);
        $changeDate->setAttribute('publish_attribute', $publishAttribute);
        $changeDate->setAttribute('past_date_state_id', $pastDateState);
        $changeDate->setAttribute('future_date_state_id', $futureDateState);

        return $changeDate;
    }

    function setAttribute($key, $value)
    {
        if (in_array($key, $this->Keys)) {
            $this->{$key} = $value;
        }
    }

    function attribute($key)
    {
        $value = false;
        if (in_array($key, $this->Keys)) {
            $value = $this->$key;
        } else if (in_array($key, $this->FunctionKeys)) {
            $functionName = array_search($key, $this->FunctionKeys);
            if ($functionName !== false) {
                $value = $this->$functionName($key);
            }
        }
        return $value;
    }

    function publishIDArray($key)
    {
        $classArray = explode(",", $this->publish_class);
        $attrArray = explode(",", $this->publish_attribute);
        $contentArray = array();
        $classCount = count($classArray);
        for ($i = 0; $i < $classCount; $i++) {
            $contentArray[] = $classArray[$i] . "-" . $attrArray[$i];
        }
        return $contentArray;
    }

    function publishClassArray($key)
    {
        return explode(",", $this->publish_class);
    }

    function publishAttributeArray($key)
    {
        return explode(",", $this->publish_attribute);
    }

    function pastDateState()
    {
        $state = false;
        $stateId = (int)$this->past_date_state_id;
        if ($stateId > 0){
            $state = eZContentObjectState::fetchById($stateId);
        }

        return $state;
    }

    function futureDateState()
    {
        $state = false;
        $stateId = (int)$this->future_date_state_id;
        if ($stateId > 0){
            $state = eZContentObjectState::fetchById($stateId);
        }

        return $state;
    }

    function hasAttribute($attr)
    {
        return in_array($attr, $this->attributes());
    }

    function attributes()
    {
        return $this->AllKeys;
    }

    function extractID($idVariable, &$idClassString, &$idAttributeString)
    {
        $idVariable = array_unique($idVariable);
        if (is_array($idVariable)) {
            foreach ($idVariable as $id) {
                list($classID, $attributeID) = explode("-", $id);
                if ($idClassString != '') {
                    $idClassString .= ',';
                }
                $idClassString .= $classID;

                if ($idAttributeString != '') {
                    $idAttributeString .= ',';
                }
                $idAttributeString .= $attributeID;
            }
        }
    }

    function classAttributes()
    {
        $db = eZDB::instance();
        $query = "SELECT DISTINCT ezcontentclass.id as contentclass_id,
                         ezcontentclass.identifier as contentclass_identifier,
                         ezcontentclass_attribute.id as contentclass_attribute_id,
                         ezcontentclass_attribute.identifier as contentclass_attribute_identifier
                  FROM ezcontentclass, ezcontentclass_attribute
                  WHERE ezcontentclass.id=ezcontentclass_attribute.contentclass_id AND
                        ezcontentclass.version = " . eZContentClass::VERSION_STATUS_DEFINED . " AND
                        ( ezcontentclass_attribute.data_type_string='ezdatetime' OR
                        ezcontentclass_attribute.data_type_string='ezdate' )
                  ORDER BY ezcontentclass.identifier";
        $resultArray = $db->arrayQuery($query);

        $contentArray = array();
        foreach ($resultArray as $result) {
            $contentArray[] = array('class' => eZContentClass::fetch($result['contentclass_id']),
                'class_attribute' => eZContentClassAttribute::fetch($result['contentclass_attribute_id']),
                'id' => $result['contentclass_id'] . '-' . $result['contentclass_attribute_id']);
        }
        return $contentArray;
    }
}
