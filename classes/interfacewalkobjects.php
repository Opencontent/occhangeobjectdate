<?php

interface InterfaceWalkObjects
{   
    public function setFetchParams( $array );
    
    public function fetchCount();

    public function fetch();
    
    public function modify( &$item, $cli );
}
?>
