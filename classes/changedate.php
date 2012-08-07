<?php
#  php extension/occhangeobjectdate/bin/php/walkobjects.php --handler=change_date -s<siteaccess> --params="<parentNodeID>;<attribute_identifier>"
class ChangeDate implements InterfaceWalkObjects
{
    public $params = array();
    private $parentNodeID;
    private $attributeIdentifier; 
    
    public function __construct( $globalParams )
    {
        $globalParams = explode( ';', $globalParams );
        $this->parentNodeID = $globalParams[0];
        $this->attributeIdentifier = $globalParams[1];
    }
    
    public function fetchCount()
    {
        $ezfModuleFunctionCollection = new ezfModuleFunctionCollection();
        $results = $ezfModuleFunctionCollection->search(
                                                       '',
                                                       0, 
                                                       1,
                                                       null,
                                                       array( 'attr_' . $this->attributeIdentifier . '_dt:[ * TO * ]' ),
                                                       null,
                                                       null,
                                                       null,
                                                       array( $this->parentNodeID )
                                                       );

        $count = $results['result']['SearchCount'];
        
        $results = $ezfModuleFunctionCollection->search(
                                                       '',
                                                       0, 
                                                       $count,
                                                       null,
                                                       array( 'attr_' . $this->attributeIdentifier . '_dt:[ * TO * ]' ),
                                                       null,
                                                       null,
                                                       null,
                                                       array( $this->parentNodeID )
                                                       );
        
        $this->results = $results['result']['SearchResult'];
        if ( $count === NULL )
        {
            $count = 0;
        }
        
        return $count;
    }
    
    public function setFetchParams( $array )
    {
        $this->params = array_merge( $this->params, $array );
    }
    
    public function fetch()
    {
        return array_slice( $this->results, $this->params['Offset'], $this->params['Limit'] );
    }
    
    public function modify( &$item, $cli )
    {
        $attributes = $item->attribute( 'object' )->fetchAttributesByIdentifier( array( $this->attributeIdentifier ) );
        foreach( $attributes as $attribute )
        {
            if ( $attribute->hasContent() )
            {
                if ( $attribute->attribute( 'object' )->attribute( 'published' ) !== $attribute->attribute( 'content' )->timeStamp() )
                {
                    $cli->warning( 'Change data for object #' . $attribute->attribute( 'contentobject_id' ) );
                    $db = eZDB::instance();
                    $db->begin();
                    $attribute->attribute( 'object' )->setAttribute( 'published', $attribute->attribute( 'content' )->timeStamp() );
                    $attribute->attribute( 'object' )->store();
                    $db->commit();
                    eZContentCacheManager::clearObjectViewCache( $attribute->attribute( 'contentobject_id' ) );
                }
            }
        }
        return;
    }
}

?>