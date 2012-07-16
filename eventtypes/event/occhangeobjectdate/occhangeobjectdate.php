<?php

class OCChangeObjectDate
{
    /*!
     Constructor
    */
    function __construct()
    {
        $this->Keys = array( 'id',
                             'version',
                             'publish_class',
                             'publish_attribute' );

        $this->FunctionKeys = array( 'classAttributes' => 'class_attributes',
                                     'publishClassArray' => 'publish_class_array',
                                     'publishAttributeArray' => 'publish_attribute_array',
                                     'publishIDArray' => 'publish_id_array');

        $this->AllKeys = array_merge( $this->Keys, $this->FunctionKeys );
        sort( $this->AllKeys );
    }

    /*!
      Create a new object
    */
    static function create( $id, $version,
                            $publishClass, $publishAttribute )
    {
        $changeDate = new OCChangeObjectDate();
        $changeDate->setAttribute( 'id', $id );
        $changeDate->setAttribute( 'version', $version );

        $changeDate->setAttribute( 'publish_class', $publishClass );
        $changeDate->setAttribute( 'publish_attribute', $publishAttribute );
        
        return $changeDate;
    }

    function setAttribute( $key, $value )
    {
        if ( in_array( $key, $this->Keys ) )
        {
            $this->$key = $value;
        }
    }

    function attribute( $key )
    {
        $value = false;
        if ( in_array( $key, $this->Keys ) )
        {
            $value = $this->$key;
        }
        else if ( in_array( $key, $this->FunctionKeys ) )
        {
            $functionName = array_search( $key, $this->FunctionKeys );
            if ( $functionName !== false )
            {
                $value = $this->$functionName( $key );
            }
        }
        return $value;
    }

    function publishIDArray( $key )
    {
        $classArray = explode( ",", $this->publish_class );
        $attrArray = explode( ",", $this->publish_attribute );
        $contentArray = array();
        $classCount = count( $classArray );
        for ( $i=0; $i < $classCount; $i++ )
        {
            $contentArray[] = $classArray[$i] . "-" . $attrArray[$i];
        }
        return $contentArray;
    }

    function publishClassArray( $key )
    {
        $value = explode( ",", $this->publish_class );
        return $value;
    }

    function publishAttributeArray( $key )
    {
        $value = explode( ",", $this->publish_attribute );
        return $value;
    }

    function attributes()
    {
        return $this->AllKeys;
    }

    function hasAttribute( $attr )
    {
        return in_array( $attr, $this->attributes() );
    }

    function extractID( $idVariable, &$idClassString, &$idAttributeString )
    {
        if ( is_array( $idVariable ) )
        {
            foreach ( $idVariable as $id )
            {
                list( $classID, $attributeID ) = explode( "-", $id );
                if ( $idClassString != '' )
                {
                    $idClassString .= ',';
                }
                $idClassString .= $classID;

                if ( $idAttributeString != '' )
                {
                    $idAttributeString .= ',';
                }
                $idAttributeString .= $attributeID;
            }
        }
    }

    function classAttributes()
    {
        $db = eZDB::instance();
        $query = "SELECT ezcontentclass.id as contentclass_id,
                         ezcontentclass.identifier as contentclass_identifier,
                         ezcontentclass_attribute.id as contentclass_attribute_id,
                         ezcontentclass_attribute.identifier as contentclass_attribute_identifier
                  FROM ezcontentclass, ezcontentclass_attribute
                  WHERE ezcontentclass.id=ezcontentclass_attribute.contentclass_id AND
                        ( ezcontentclass_attribute.data_type_string='ezdatetime' OR
                        ezcontentclass_attribute.data_type_string='ezdate' )
                  ORDER BY ezcontentclass.identifier";
        $resultArray = $db->arrayQuery( $query );

        $contentArray = array();
        foreach ( $resultArray as $result )
        {
            $contentArray[] = array( 'class' => eZContentClass::fetch( $result['contentclass_id'] ),
                                'class_attribute' => eZContentClassAttribute::fetch( $result['contentclass_attribute_id'] ),
                                'id' => $result['contentclass_id'] . '-' . $result['contentclass_attribute_id'] );
        }
        return $contentArray;
    }

    var $Keys;
}

?>
