<?php

class OCChangeObjectDateType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'occhangeobjectdate';
    const PUBLISH_CLASS = 'data_text1';
    const PUBLISH_ATTRIBUTE = 'data_text2';

    /*!
     Constructor
    */
    function OCChangeObjectDateType()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING,  ezpI18n::tr( 'occhangeobjectdate/event', "Change object publish date" ) );
        $this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'after' ) ) ) );
    }

    /*!
      Executes the workflow.
    */
    function execute( $process, $event )
    {
        $returnStatus = eZWorkflowType::STATUS_ACCEPTED;

        $parameters = $process->attribute( 'parameter_list' );
        $object = eZContentObject::fetch( $parameters['object_id'] );

        if ( !$object )
        {
            eZDebugSetting::writeError( 'extension-workflow-changeobjectdate','The object with ID ' . $parameters['object_id'] . ' does not exist.', 'OCChangeObjectDateType::execute() object is unavailable' );
            return eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
        }

        // if a newer object is the current version, abort this workflow.
        $currentVersion = $object->attribute( 'current_version' );
        $version = $object->version( $parameters['version'] );

        if (!$version)
        {
            eZDebugSetting::writeError( 'The version of object with ID ' . $parameters['object_id'] . ' does not exist.', 'OCChangeObjectDateType::execute() object is unavailable' );
            return eZWorkflowType::STATUS_WORKFLOW_CANCELLED;

        }

        $objectAttributes = $version->attribute( 'contentobject_attributes' );

        $changeDateObject = $this->workflowEventContent( $event );

        $publishAttributeArray = $changeDateObject->attribute( 'publish_attribute_array' );

        $publishAttribute = false;

        foreach ( $objectAttributes as $objectAttribute )
        {
            $contentClassAttributeID = $objectAttribute->attribute( 'contentclassattribute_id' );
            if ( in_array( $contentClassAttributeID, $publishAttributeArray ) )
            {
                $publishAttribute = $objectAttribute;
            }
        }

        if ( $publishAttribute instanceof eZContentObjectAttribute && $publishAttribute->attribute( 'has_content' ) )
        {
            $date = $publishAttribute->attribute( 'content' );
            if ( $date instanceof eZDateTime || $date instanceof eZDate )
            {
                $object->setAttribute( 'published', $date->timeStamp() );
                $object->store();
                eZDebug::writeNotice( 'Workflow change object publish date', __METHOD__ );
            }
        }

        return $returnStatus;
    }

    function attributes()
    {
        return array_merge( array( 'class_attributes' ),
                            eZWorkflowEventType::attributes() );
    }

    function hasAttribute( $attr )
    {
        return in_array( $attr, $this->attributes() );
    }

    function attribute( $attr )
    {
        $value = false;
        switch ( $attr )
        {
            case "class_attributes":
            {
                $changeDate = new OCChangeObjectDate();
                $value = $changeDate->attribute( 'class_attributes' );
            } break;

            default:
            {
                $value = parent::attribute( $attr );
            }
        }
        return $value;
    }

    /*!
      Fetch the different post variables.
    */
    function fetchHTTPInput( $http, $base, $event )
    {
        $doUpdate = $base . "_data_changeobjectdate_do_update_" . $event->attribute( "id" );
        if ( $http->hasPostVariable( $doUpdate ) )
        {
            $changeDate = new OCChangeObjectDate();

            $publishDateVariable = $base . "_data_changeobjectdate_attribute_" . $event->attribute( "id" );
            $publishDateClassString = '';
            $publishDateAttributeString = '';
            if ( $http->hasPostVariable( $publishDateVariable ) )
            {
                $publishDateValue = $http->postVariable( $publishDateVariable );
                $changeDate->extractID( $publishDateValue, $publishDateClassString, $publishDateAttributeString );
            }
            $event->setAttribute( 'data_text1', $publishDateClassString );
            $event->setAttribute( 'data_text2', $publishDateAttributeString );
        }
    }

    /*!
      Return the content of the event.
    */
    function workflowEventContent( $event )
    {
        $id = $event->attribute( "id" );
        $version = $event->attribute( "version" );

        $publishClass = $event->attribute( self::PUBLISH_CLASS );
        $publishAttribute = $event->attribute( self::PUBLISH_ATTRIBUTE );

        $changeDate = OCChangeObjectDate::create( $id, $version,
                                                $publishClass, $publishAttribute );
        return $changeDate;
    }
}

eZWorkflowEventType::registerEventType( OCChangeObjectDateType::WORKFLOW_TYPE_STRING, "occhangeobjectdatetype" );

?>
