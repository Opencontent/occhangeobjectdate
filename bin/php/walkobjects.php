#!/usr/bin/env php
<?php
set_time_limit ( 0 );
require 'autoload.php';

$siteINI = eZINI::instance();

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => "Walk Objects",
                                      'use-session' => true,
                                      'use-modules' => true,
                                      'use-extensions' => true,
                                      'user' => array( 'login' => 'admin', 'password' => 'gabricecek' )) );

$script->startup();

$script->initialize();

$handlers = eZINI::instance( 'walkobjects.ini' )->variable( 'WalkObjectsHandlers', 'AvaiableHandlers' );
$params = array();
$params[] = "Per handler params:";
foreach( $handlers as $handler )
{
    $class = eZINI::instance( 'walkobjects.ini' )->variable( $handler, 'PHPClass' );
    $params[] = $handler . ": " . $class::help();
}

$options = $script->getOptions( "[handler:][params:]",
                                "",
                                array(
                                      'handler' => "Handler name stored in walkobjects.ini",
                                      'params' => implode( "\n", $params )
                                      )
                              );


$handlerName = $options['handler'];
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