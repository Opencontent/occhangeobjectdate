<?php
#  php extension/occhangeobjectdate/bin/php/walkobjects.php --handler=republish_objects -s<siteaccess> --params="<parentNodeID>;<classIdentifier>;<limit>"
class RepublishObjects implements InterfaceWalkObjects
{

    public $params = array( 'Limitation'  => array(),
                            'ClassFilterType' => 'include',
                            'LoadDataMap' => false
                          );

    public $parentNodeID = 0;
    public $classIdentifier = '';
    public $limit = 0;

    public $logFile = 'republish_objects.log'; #cambiare nome
    public $logDir = 'var/log';
    public $errors = array();
    
    public static function help()
    {
        return '--handler=republish_objects -s<siteaccess> --params="<parentNodeID>;<classIdentifier>;<limit>';
    }
    
    public function __construct( $globalParams = array() )
    {
        $this->errors = array();

        $globalParams = explode( ';', $globalParams );

        //
        if($globalParams[0]!=null){

            $node = eZContentObjectTreeNode::fetch($globalParams[0]);

            if ( !$node instanceof eZContentObjectTreeNode )
            {
                array_push($this->errors, 'Non esiste un nodo con id '.$globalParams[0]);
            }else{
                $this->parentNodeID = $globalParams[0];
            }

        }else{
            array_push($this->errors, 'Valorizzare parentNodeID: --params="<parentNodeID>;<classIdentifier>;<limit>"');
        }

        //
        if($globalParams[1]!=null){

            $class = eZContentClass::fetchByIdentifier( $globalParams[1] );

            if ( !$class instanceof eZContentClass )
            {
                array_push($this->errors, 'Non esiste una classe con identificativo '.$globalParams[1]);
            }else{
                $this->classIdentifier = $globalParams[1];
            }


        }else{
            array_push($this->errors, 'Valorizzare classIdentifier: --params="<parentNodeID>;<classIdentifier>;<limit>"');
        }

        //limit
        if($globalParams[2]!=null){

            if (!is_numeric($globalParams[2]))
            {
                array_push($this->errors, $globalParams[2].' non è un valore intero per gestire il limit della query');
            }else{
                $this->limit = $globalParams[2];
            }
        }

        if(count($this->errors) > 0){

            echo "\n"."-----------------------------------------------------------------";
            foreach($this->errors as $error){
                echo "\n".$error;
            }
            echo "\n"."-----------------------------------------------------------------"."\n";

            die();
        }
    }
    
    public function fetchCount()
    {
        return count($this->fetch());
    }
    
    public function setFetchParams( $array )
    {
        $this->params = array_merge( $this->params, $array );
    }
    
    public function fetch()
    {
        $this->params['ClassFilterArray'] = array($this->classIdentifier);

        //se l'ho passato setto il limit
        if($this->limit > 0){
            $this->params['Limit'] = (int)$this->limit;
        }

        return eZContentObjectTreeNode::subTreeByNodeID( $this->params, $this->parentNodeID );
    }
    
    public function modify( &$item, $cli )
    {
		try{

			$object = $item->attribute( 'object' );

			eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $object->attribute( 'id' ), 'version' => $object->attribute( 'current_version' ) ) );

            $object->resetDataMap();
			eZContentObject::clearCache( $object->attribute( 'id' ) );

            echo "\n"."Ripubblicato object_id: ".$object->attribute( 'id' )." - ".$object->attribute( 'name' );

        } catch (Exception $e) {
			echo "\n"."Eccezione: ".  $e->getMessage();
		}
		
        return;
    }
	
}

?>