<?php
#  php extension/occhangeobjectdate/bin/php/walkobjects.php --handler=replace_in_attribute -s<siteaccess> --params="<parentNodeID>;<classIdentifier>;<classAttribute>;<textToFind>;<textToReplace>;<limit>"
class ReplaceInAttribute implements InterfaceWalkObjects
{

    public $params = array( 'Limitation'  => array(),
                            'ClassFilterType' => 'include',
                            'LoadDataMap' => false
                          );

    public $parentNodeID = 0;
    public $classIdentifier = '';
    public $textToReplace = '';
    public $textToFind = '';
    public $attributeIdentifier = '';
    public $limit = 0;

    public $logFile = 'replace_in_attribute.log'; #cambiare nome
    public $logDir = 'var/log';
    public $errors = array();
    
    public static function help()
    {
        return '--handler=replace_in_attribute -s<siteaccess> --params="<parentNodeID>;<classIdentifier>;<classAttribute>;<textToFind>;<textToReplace>;<limit>"';
    }
    
    public function __construct( $globalParams = array() )
    {
        $this->errors = array();

        $globalParams = explode( ';', $globalParams );

        //controllo se il nodo esiste
        if($globalParams[0]!=null){

            $node = eZContentObjectTreeNode::fetch($globalParams[0]);

            if ( !$node instanceof eZContentObjectTreeNode )
            {
                array_push($this->errors, 'Non esiste un nodo con id '.$globalParams[0]);
            }else{
                $this->parentNodeID = $globalParams[0];
            }

        }else{
            array_push($this->errors, 'Valorizzare parentNodeID: --params="<parentNodeID>;<classIdentifier>;<classAttribute>;<textToFind>;<textToReplace>;<limit>"');
        }

        //controllo se esiste la classe
        if($globalParams[1]!=null){

            $class = eZContentClass::fetchByIdentifier( $globalParams[1] );

            if ( !$class instanceof eZContentClass )
            {
                array_push($this->errors, 'Non esiste una classe con identificativo '.$globalParams[1]);
            }else{
                $this->classIdentifier = $globalParams[1];
            }


        }else{
            array_push($this->errors, 'Valorizzare classIdentifier: --params="<parentNodeID>;<classIdentifier>;<classAttribute>;<textToFind>;<textToReplace>;<limit>"');
        }


        //controllo se esiste l'attributo della classe
        if($globalParams[2]!=null){

            $classAttribute = $globalParams[1]."/".$globalParams[2];
            $classAttributeID = eZContentObjectTreeNode::classAttributeIDByIdentifier( $classAttribute );

            if (!$classAttributeID)
            {
                array_push($this->errors, $globalParams[2].' non è un attributo della classe '.$globalParams[1]);

            }else{


                $classAttributeObj = eZContentClassAttribute::fetch( $classAttributeID );

                if($classAttributeObj instanceof eZContentClassAttribute){

                    $data_type_string = $classAttributeObj->DataTypeString;

                    if($data_type_string!='ezstring'){

                        array_push($this->errors, $globalParams[2].' non è un attributo di tipo ezstring');

                    }else{

                        $this->attributeIdentifier = $globalParams[2];
                    }
                }

                $this->attributeIdentifier = $globalParams[2];
            }


        }else{
            array_push($this->errors, 'Valorizzare attributeIdentifier: --params="<parentNodeID>;<classIdentifier>;<classAttribute>;<textToFind>;<textToReplace>;<limit>"');
        }

        //controllo se è stata imposta la stringa da ricercare nel testo dell'attributo
        if($globalParams[3]!=null){

            $this->textToFind = $globalParams[3];

        }else{
            array_push($this->errors, 'Valorizzare textToFind --params="<parentNodeID>;<classIdentifier>;<classAttribute>;<textToFind>;<textToReplace>;<limit>"');
        }

        //controllo se è stata imposta la stringa da sostituire
        if($globalParams[4]!=null){

            $this->textToReplace = $globalParams[4];

        }else{
            array_push($this->errors, 'Valorizzare textToReplace --params="<parentNodeID>;<classIdentifier>;<classAttribute>;<textToFind>;<textToReplace>;<limit>"');
        }

        //se il limit è impostato controllo che sia un numerico
        if($globalParams[5]!=null){

            if (!is_numeric($globalParams[5]))
            {
                array_push($this->errors, $globalParams[5].' non è un valore intero per gestire il limit della query');
            }else{
                $this->limit = $globalParams[5];
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

            $data_map = $object->dataMap();
            $attribute = $data_map[$this->attributeIdentifier];
            $data_value = $attribute->content();

            if (strpos($data_value, $this->textToFind) !== false) {

                $data_value_final = str_replace($this->textToFind, $this->textToReplace, $data_value);

                $attribute->setAttribute('data_text', $data_value_final);
                $attribute->store();


                /*

                decommentami se vuoi avere un prompt per ogni riga

                echo "\n"."Controlla se la sostituzione ha prodotto il risultato desiderato (object_id = ".$object->ID.")";
                echo "\n".$data_value_final;

                $menuchoice = readline("\n"."Digita (S/N): ");

                if ( $menuchoice == 'S') {
                    $attribute->setAttribute('data_text', $data_value_final);
                    $attribute->store();
                }else{
                    echo "\n"."Sostituzione annullata";
                }
                */

                //eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $object->attribute( 'id' ), 'version' => $object->attribute( 'current_version' ) ) );

                //$object->resetDataMap();
                //eZContentObject::clearCache( $object->attribute( 'id' ) );

                //echo "\n"."Ripubblicato object_id: ".$object->attribute( 'id' )." - ".$object->attribute( 'name' );

            }else{

                echo "\n"."Il testo ".$this->textToFind." non è stato trovato";
            }


        } catch (Exception $e) {
			echo "\n"."Eccezione: ".  $e->getMessage();
		}
		
        return;
    }
	
}

?>