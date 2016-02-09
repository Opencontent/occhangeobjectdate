<?php
#  php extension/occhangeobjectdate/bin/php/walkobjects.php --handler=change_objects_state_and_republish -s<siteaccess> --params="<parentNodeID>;<classIdentifier>;<objectStateId>"
class ChangeObjectsStateAndRepublish implements InterfaceWalkObjects
{

    public $params = array( 'Limitation'  => array(),
                            'ClassFilterType' => 'include',
                            'LoadDataMap' => false
                          );

    public $parentNodeID = 0;
    public $classIdentifier = '';
    public $stateIdentifier;
    public $limit = 0;

    public $logFile = 'change_objects_state_and_republish.log'; #cambiare nome
    public $logDir = 'var/log';
    public $errors = array();
    
    public static function help()
    {
        return '--handler=change_objects_state_and_republish -s<siteaccess> --params="<parentNodeID>;<classIdentifier>;<objectStateId>';
    }
    
    public function __construct( $globalParams = array() )
    {

        $globalParams = explode( ';', $globalParams );

        //controllo che sia impostato il nodo padre e che esista
        if($globalParams[0]!=null){


            $node = eZContentObjectTreeNode::fetch($globalParams[0]);

            if ( !$node instanceof eZContentObjectTreeNode )
            {
                array_push($this->errors, 'Non esiste un nodo con id '.$globalParams[0]);
            }else{
                $this->parentNodeID = $globalParams[0];
            }

        }else{
            array_push($this->errors, 'Valorizzare parentNodeID: --params="<parentNodeID>;<classIdentifier>;<objectStateId>"');
        }

        //controllo che sia impostata la classe e che sia realmente esistente
        if($globalParams[1]!=null){

            $class = eZContentClass::fetchByIdentifier( $globalParams[1] );

            if ( !$class instanceof eZContentClass )
            {
                array_push($this->errors, 'Non esiste una classe con identificativo '.$globalParams[1]);
            }else{
                $this->classIdentifier = $globalParams[1];
            }


        }else{
            array_push($this->errors, 'Valorizzare classIdentifier: --params="<parentNodeID>;<classIdentifier>;<objectStateId>;<limit>"');
        }

        //controllo che sia stato impostato l'id dello stato da impostare e che esista
        if($globalParams[2]!=null){

            $state = eZContentObjectState::fetchById( $globalParams[2] );

            if ( !$state instanceof eZContentObjectState )
            {
                array_push($this->errors, 'Non esiste uno stato con id '.$globalParams[2]);
            }else{
                $this->stateIdentifier = $globalParams[2];
            }


        }else{
            array_push($this->errors, 'Valorizzare objectStateId: --params="<parentNodeID>;<classIdentifier>;<objectStateId>;<limit>"');
        }

        //limit
        if($globalParams[3]!=null){

            if (!is_numeric($globalParams[3]))
            {
                array_push($this->errors, $globalParams[3].' non è un valore intero per gestire il limit della query');
            }else{
                $this->limit = $globalParams[3];
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

            eZContentOperationCollection::updateObjectState( $object->attribute( 'id' ), array($this->stateIdentifier) );

			eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $object->attribute( 'id' ), 'version' => $object->attribute( 'current_version' ) ) );

            $object->resetDataMap();
            eZContentObject::clearCache( $object->attribute( 'id' ) );

            echo "\n"."Stato modificato per object_id: ".$object->attribute( 'id' )." - ".$object->attribute( 'name' );

        } catch (Exception $e) {
			echo "\n"."Eccezione: ".  $e->getMessage();
		}
		
        return;
    }
	
}

?>