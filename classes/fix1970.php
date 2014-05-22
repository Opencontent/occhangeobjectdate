<?php
#  php extension/occhangeobjectdate/bin/php/walkobjects.php --handler=fix1970 -s<siteaccess>"
class Fix1970 implements InterfaceWalkObjects
{
    public $params = array( 'Limitation'  => array(),
                            'ClassFilterType' => 'include',
                            'LoadDataMap' => false
                          );
    public $parentNodeID = 0;
    public $attributeIdentifiers = array();
    public $toSectionDefault = array();
    public $toSectionCustoms = array();
    public $timeAddCustoms = array();
    public $logFile = 'fix1970.log';
    public $logDir = 'var/log';
    
    public static function help()
    {
        return '--handler=fix1970 -s<siteaccess>';
    }
    
    public function __construct( $globalParams = array() )
    {        
        $this->logDir = eZINI::instance()->variable( 'FileSettings', 'VarDir' ) . eZSys::fileSeparator() . eZINI::instance()->variable( 'FileSettings', 'LogDir' );        
        $this->parentNodeID     = eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' );        
    }
    
    public function fetchCount()
    {
        
        $count = eZContentObjectTreeNode::subTreeCountByNodeID( $this->params, $this->parentNodeID );        
        
        if ( $count == NULL )
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
        return eZContentObjectTreeNode::subTreeByNodeID( $this->params, $this->parentNodeID );
    }
    
    public function modify( &$item, $cli )
    {                        
        $done = false;
        $db = eZDB::instance();
        $object = $item->attribute( 'object' );
        if( $object instanceof eZContentObject === false )
        {
            continue;
        }
        $attributes = $object->fetchAttributesByIdentifier( array( 'anno' ) );
        foreach( $attributes as $attribute )
        {
            if ( $attribute->hasContent() )
            {                    
                if ( $attribute->content() == 1970 )
                {
                    $db->begin();
                    $attribute->fromString( '' );
                    $attribute->store();
                    $db->commit();
                    eZLog::write( "Reset anno per NodeID #{$item->attribute( 'node_id' )}" );
                    eZContentCacheManager::clearContentCacheIfNeeded( $object->attribute( 'id' ) );
                    eZContentOperationCollection::registerSearchObject( $object->attribute( 'id' ), false );
                    $done = true;
                }                
            }
        }
        eZContentObject::clearCache( $object->attribute( 'id' ) );
        $object->resetDataMap();
        
        if ( $done )
            $cli->notice( '*', false );
        else
            $cli->notice( '.', false );
        return;
    }
}

?>