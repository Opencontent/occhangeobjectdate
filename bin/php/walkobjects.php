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
                                      ) );

$script->startup();

$options = $script->getOptions( "[handler:][params:]",
                                "",
                                array(
                                      'handler' => "Handler name stored in walkobjects.ini"                                     
                                      )
                              );

$script->initialize();

$isQuiet = false;

if ( $isQuiet )
{    
    $cli->setIsQuiet( true );
}


$handlers = eZINI::instance( 'walkobjects.ini' )->variable( 'WalkObjectsHandlers', 'AvaiableHandlers' );
$params = array();
$params[] = "Per handler params:";
foreach( $handlers as $handler )
{
    $class = eZINI::instance( 'walkobjects.ini' )->variable( $handler, 'PHPClass' );
    $params[] = $handler . ": " . $class::help();
}

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

$user = eZUser::fetchByName('admin');
eZUser::setCurrentlyLoggedInUser( $user, $user->attribute( 'contentobject_id' ) );

$contentObjects = array();

$count = $handler->fetchCount();
$cli->notice( "Number of objects to walk: $count" );

$length = 50;
$handler->setFetchParams( array( 'Offset' => 0 , 'Limit' => $length ) );

$output = new ezcConsoleOutput();
$progressBarOptions = array(
    'emptyChar'         => ' ',
    'barChar'           => '='
);
if ( $isQuiet )
{
    $progressBarOptions['minVerbosity'] = 10;    
}
$progressBar = new ezcConsoleProgressbar( $output, intval( $count ), $progressBarOptions );
$progressBar->start();

do
{
    $items = $handler->fetch();
    
    foreach ( $items as $item )
    {            
        if ( $handler )
        {
            $progressBar->advance();
            $handler->modify( $item, $cli );
        }
    }
    
    $handler->params['Offset'] += $length;
} while ( count( $items ) == $length );

$progressBar->finish();
$memoryMax = memory_get_peak_usage(); // Result is in bytes
$memoryMax = round( $memoryMax / 1024 / 1024, 2 ); // Convert in Megabytes
$cli->notice( 'Peak memory usage : '.$memoryMax.'M' );

$script->shutdown();
?>