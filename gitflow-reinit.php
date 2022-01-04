#!/usr/bin/env php
<?php

/**
 *  NEED THIS PACKAGE: sudo pear install Config_Lite-0.2.6
 */
require_once 'Config/Lite.php';

/**
 *  Get Console Options
 */
$opt    = getopt( 'n:' );
if ( ! isset( $opt['n'] ) ) { // Display Help
    echo Usage() . "\n";
    exit( 0 );
}

/**
 * MAIN SCRIPT
 * ////////////////////////////////////
 */
$cwd    = getcwd();
$config = new Config_Lite();

$config->setQuoteStrings( false );
$config->read( "{$cwd}/.git/config", INI_SCANNER_RAW );

$oldBranch  = $config->getString( 'gitflow "branch"', 'develop' );
$newBranch  = $opt['n'];

exec ( "git checkout -b {$newBranch}" );
$config->setString( 'gitflow "branch"', 'develop', $newBranch );
$config->save();

exec ( "git branch -d {$oldBranch}" );

echo "SUCCESS !!!\n";

/**
 * Print Usage
 */
function Usage()
{
    $usage = "
============================================================================================================================================ \n
= Usage \n
============================ \n
= \n
= -n version    Version for which to create branch
= \n
= gitflow-reinit -n1.5 \n
============================================================================================================================================ \n
";

    return $usage;
}
