<?php
#  php extension/occhangeobjectdate/bin/php/walkobjects.php --handler=fix_date_concorso_albo -s<siteaccess>"
class FixDateConcorsoAlbo implements InterfaceWalkObjects
{
    public $params;
    public $contentClass;
    
    public static function help()
    {
        return '--handler=fix_date_concorso_albo -s<siteaccess>';
    }
    
    public function __construct( $globalParams = array() )
    {
        $this->contentClass = eZContentClass::fetchByIdentifier( 'concorso' );
    }
    
    public function fetchCount()
    {        
        $count = eZContentObject::fetchSameClassListCount( $this->contentClass->attribute( 'id' ) );        
        
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
        return eZContentObject::fetchSameClassList( $this->contentClass->attribute( 'id' ), true, $this->params['Offset'], $this->params['Limit']  );
    }
    
    public function modify( &$object, $cli )
    {                        
        $done = false;
        
        $isAlbo = strpos( $object->attribute( 'remote_id' ), 'at_' ) !== false;
        $isOriginal = $object->attribute( 'current_version' ) == 1;
        
        if ( !$isOriginal )
        {
            $ownerID = $object->attribute( 'owner_id' );
            $owner = eZUser::fetch( $ownerID );
            if ( $owner )
            {
                if ( strpos( $owner->attribute( 'email' ), '@opencontent.it' ) !== false )
                {
                    $isOriginal = true;
                }
            }
        }
       
        if ( $isAlbo && $isOriginal )
        {
            $db = eZDB::instance();
            $db->begin();
            $dataMap = $object->attribute( 'data_map' );
            if ( isset( $dataMap['data_inizio_validita'] ) && $dataMap['data_inizio_validita']->hasContent() )
            {
                $dataMap['data_inizio_validita']->fromString( '0' );
                $dataMap['data_inizio_validita']->store();
            }
            if ( isset( $dataMap['data_fine_validita'] ) && $dataMap['data_fine_validita']->hasContent() )
            {
                $dataMap['data_fine_validita']->fromString( '0' );
                $dataMap['data_fine_validita']->store();
            }
            $db->commit();
            eZContentCacheManager::clearContentCacheIfNeeded( $object->attribute( 'id' ) );
            eZContentOperationCollection::registerSearchObject( $object->attribute( 'id' ), false );
            $done = true;
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