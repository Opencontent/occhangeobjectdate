<?php
#  php extension/occhangeobjectdate/bin/php/walkobjects.php --handler=change_section -s<siteaccess>"
class ChangeSection implements InterfaceWalkObjects
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
    public $logFile = 'change_section.log';
    public $logDir = 'var/log';
    
    public static function help()
    {
        return '--handler=change_section -s<siteaccess>';
    }
    
    public function __construct( $globalParams = array() )
    {        
        $this->logDir = eZINI::instance()->variable( 'FileSettings', 'VarDir' ) . eZSys::fileSeparator() . eZINI::instance()->variable( 'FileSettings', 'LogDir' );        
        $this->parentNodeID     = eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' );
        $classAttributes        = eZINI::instance( 'openpa.ini' )->variable( 'Attributi', 'AttributiDataArchiviazione' );
        $this->toSectionDefault       = eZINI::instance( 'openpa.ini' )->variable( 'ChangeSection', 'ToSectionDefault' );
        $this->toSectionCustoms       = eZINI::instance( 'openpa.ini' )->variable( 'ChangeSection', 'ToSection' );
        $this->timeAddCustoms         = eZINI::instance( 'openpa.ini' )->variable( 'ChangeSection', 'ScadeDopoTotSecondi' );
        foreach( $classAttributes as $classAttribute )
        {
            $classAttribute = explode( '/', $classAttribute );
            if ( is_array( $classAttribute ) && count( $classAttribute ) > 1 )
            {
                $this->attributeIdentifiers[$classAttribute[0]][] = $classAttribute[1];
            }
        }
        $classes = array_keys( $this->attributeIdentifiers );
        $this->params['ClassFilterArray'] = $classes;        
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
    
    public function getSectionID( $string )
    {
        global $cli;        
        if ( !is_numeric( $string ) )
        {                    
            $sectionObject = eZSection::fetchByIdentifier( $string, false );
        }
        else
        {
            $sectionObject = eZSection::fetch( $string, false );
        }
            
        if ( is_array( $sectionObject ) && !empty( $sectionObject ) )
        {                        
            return $sectionObject['id'];
        }
        $cli->error( "Section $string non trovata" );
        return false;
    }
    
    public function modify( &$item, $cli )
    {                        
        $done = false;
        if ( isset( $this->attributeIdentifiers[$item->attribute( 'class_identifier' )] ) )
        {
            $toSection = false;
            
            if ( isset( $this->toSectionCustoms[$item->attribute( 'class_identifier' )] ) )
            {                
                $toSection = $this->getSectionID( $this->toSectionCustoms[$item->attribute( 'class_identifier' )] );
            }
            
            if ( !$toSection )
            {
                $toSection = $this->getSectionID( $this->toSectionDefault );
            }
            
            $object = $item->attribute( 'object' );
            if( $object instanceof eZContentObject === false )
            {
                continue;
            }            
            $attributes = $object->fetchAttributesByIdentifier( $this->attributeIdentifiers[$item->attribute( 'class_identifier' )] );
            foreach( $attributes as $attribute )
            {
                if ( $attribute->hasContent() )
                {                    
                    if ( isset( $this->timeAddCustoms[$item->attribute( 'class_identifier' )] ) )
                    {
                        $timeAdd = $this->timeAddCustoms[$item->attribute( 'class_identifier' )];
                    }
                    else
                    {
                        $timeAdd = 9262300400; // ??    
                    }
                    
                    $unpublishTimestamp = $attribute->attribute( 'content' )->timeStamp();
                    if ( $unpublishTimestamp <= 0 )
                    {
                        $unpublishTimestamp = $object->attribute( 'published' ) + $timeAdd;
                    }
                    $currentDate = time();
                    
                    // fine giornata della data di spubblicazione
                    if ( $unpublishTimestamp > 0 )
                    {
                        $unpublishTimestamp = mktime( 23, 59, 59, date("n", $unpublishTimestamp), date("j", $unpublishTimestamp), date("Y", $unpublishTimestamp) );
                    }
                    
                    if (
                            $toSection !== false
                        &&  $unpublishTimestamp > 0
                        &&  $unpublishTimestamp < $currentDate
                        &&  $object->attribute( 'section_id' ) !== $toSection
                        )
                    {                        
                        $fromSection = $object->attribute( 'section_id' );
                        if ( eZOperationHandler::operationIsAvailable( 'content_updatesection' ) )
                        {
                            $operationResult = eZOperationHandler::execute( 'content',
                                                                            'updatesection',
                                                                            array( 'node_id'             => $item->attribute( 'node_id' ),
                                                                                   'selected_section_id' => $toSection ),
                                                                            null,
                                                                            true );
                        
                        }
                        else
                        {
                            eZContentOperationCollection::updateSection( $item->attribute( 'node_id' ), $toSection );
                        }
                        eZLog::write( "NodeID #{$item->attribute( 'node_id' )} From sectionID #$fromSection to sectionID #$toSection", $this->logFile, $this->logDir );
                        eZContentCacheManager::clearContentCacheIfNeeded( $object->attribute( 'id' ) );
                        $done = true;
                    }
                }
            }
            eZContentObject::clearCache( $object->attribute( 'id' ) );
            $object->resetDataMap();
        }
        
        if ( $done )
            $cli->notice( '*', false );
        else
            $cli->notice( '.', false );
        return;
    }
}

?>