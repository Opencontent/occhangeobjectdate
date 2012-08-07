#!/usr/bin/env php
<?php
set_time_limit ( 0 );
require 'autoload.php';

$siteINI = eZINI::instance();

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "Walk Objects" ),
                                      'use-session' => true,
                                      'use-modules' => true,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[handler:][params:]",
                                "",
                                array(
                                      'handler' => "Handler name stored in walkobjects.ini",
                                      'params' => "Per handler params"
                                      )
                              );
$script->initialize();

$handlerName = $options['handler'];
$handlers = eZINI::instance( 'walkobjects.ini' )->variable( 'WalkObjectsHandlers', 'AvaiableHandlers' );
$handler = false;
if ( in_array( $handlerName, $handlers ) )
{
    if ( eZINI::instance( 'walkobjects.ini' )->hasVariable( $handlerName, 'PHPClass' ) )
    {
        $class = eZINI::instance( 'walkobjects.ini' )->variable( $handlerName, 'PHPClass' );
        $handler = new $class( $options['params'] );
    }
}

if ( !$handler )
{
    $cli->error( "No handler found" );
    $script->shutdown();
    eZExecution::cleanExit();
}

$contentObjects = array();

$count = $handler->fetchCount();
$cli->notice( "Number of objects to walk: $count" );

$length = 50;
$handler->setFetchParams( array( 'Offset' => 0 , 'Limit' => $length ) );

$script->resetIteration( $count );

$user = eZUser::fetchByName( 'admin' );
if ( !$user )
{
    $user = eZUser::currentUser();
}
eZUser::setCurrentlyLoggedInUser( $user, $user->attribute( 'contentobject_id' ) );

do
{
    $items = $handler->fetch();
    
    foreach ( $items as $item )
    {            
        if ( $handler )
        {
            $handler->modify( &$item, $cli );
        }
    }
    
    $handler->params['Offset'] += $length;
} while ( count( $items ) == $length );


$script->shutdown();
?>