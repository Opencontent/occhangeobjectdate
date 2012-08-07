<?php
#  php extension/occhangeobjectdate/bin/php/walkobjects.php --handler=dummy_handler -s<siteaccess> --params="Node name:"
class DummyHandler implements InterfaceWalkObjects
{
    public $params = array( 'ClassFilterType' => 'include', 'ClassFilterArray' => array( 'image' ) );
    public $globalParams;
    
    public function __construct( $globalParams )
    {
        $this->globalParams = $globalParams;
    }
    
    public function fetchCount()
    {
        $count = eZContentObjectTreeNode::subTreeCountByNodeID( $this->params, 193062 );
        
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
        return eZContentObjectTreeNode::subTreeByNodeID( $this->params, 193062 );
    }
    
    public function modify( &$item, $cli )
    {
        $cli->output( $this->globalParams . ' ' . $item->attribute( 'name' ) );
        return;
    }
}

?>