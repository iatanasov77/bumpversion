#!/usr/bin/php
<?php

/**
 * GLOBAL CONFIGURATION
 * ////////////////////////////////////
 */
$versionFile		= 'VERSION';
$changesFile		= 'CHANGELOG.md';
$tempChangesFile	= 'TMP_CHANGELOG.md';
$editor				= '/usr/bin/vim';
$changesRowPrefix	= '* ';
$initialVersion		= '0.0.0';
$tagPrefix          = 'v';
$opt				= getopt( 'dh' );

/**
 * SUPPORT VARS PASSED BY REFERENCE
 * ////////////////////////////////////
 */
$lastVersion;
$suggestedVersion;

/**
 * MAIN SCRIPT
 * ////////////////////////////////////
 */
if ( isset( $opt['h'] ) ) { // Display Help
    echo Usage() . "\n";
    exit( 0 );
}
evalNewVersion( $lastVersion, $suggestedVersion );

$changes    = fetchChanges();
if ( isset( $opt['d'] ) ) { // Dry-Run: Only Display Current Version and Changes
    echo $changes;
    exit( 0 );
}

applyChanges( $changes );
file_put_contents( $versionFile, $suggestedVersion );

// Commit VERSION and CHANGELOG.md files
exec( sprintf( 'git add %s %s', basename( $versionFile ), basename( $changesFile ) ) );
exec( sprintf( 'git commit -m "Version bump to %s"', $suggestedVersion ) );


/**
 * FUNCTIONS
 * ////////////////////////////////////
 */

function evalNewVersion( &$lastVersion, &$suggestedVersion)
{
    global $versionFile, $initialVersion, $opt;
    
    if( ! file_exists( $versionFile ) )
    {
        file_put_contents( $versionFile, $initialVersion );
    }
    
    $lastVersion		= file_get_contents( $versionFile );
    printf( "Current version : %s\n", $lastVersion );
    list( $versionMajor, $versionMinor, $versionPatch )	= explode('.', $lastVersion);
    
    // Set New Version
    $versionMinor++;
    $versionPatch		= 0;
    $suggestedVersion	= sprintf( "%d.%d.%d", $versionMajor, $versionMinor, $versionPatch );
    
    if ( ! isset( $opt['d'] ) ) { // Dry-Run: Only Display Current Version and Changes
        printf( "Enter a version number [%s]: ", $suggestedVersion );
        $input				= trim( fgets( STDIN ) );
        $suggestedVersion   = empty( $input ) ? $suggestedVersion : $input;
    }
}

function fetchChanges()
{
    global $changesRowPrefix, $initialVersion, $lastVersion, $suggestedVersion, $tagPrefix, $opt;
    
    // Fetch GIT CHANGES , edit its and prepend in the CHANGES file
    $gitLogCommand		= ( $lastVersion === $initialVersion )
                            ? sprintf( 'git log --pretty=format:"%%x09[%%ai][Commit: %%H]%%n%%x09  %%s"' )
                            : sprintf( 'git log --pretty=format:"%%x09[%%ai][Commit: %%H]%%n%%x09  %%s"  %s%s...HEAD', $tagPrefix, $lastVersion );
    
    if ( isset( $opt['d'] ) ) { // Dry-Run: Only Display Current Version and Changes
        $changes			= sprintf(
                            "DryRun ( Display Changes Only )\n================================\n* Commits:\n%s\n\n",
                            shell_exec( $gitLogCommand )
                        );
    } else {
        $changes			= sprintf(
                            "%s\t|\tRelease date: **%s**\n============================================\n* New Features:\n* Bug-Fixes:\n* Commits:\n%s\n\n",
                            $suggestedVersion,
                            date( "d.m.Y" ),
                            shell_exec( $gitLogCommand )
                        );
    }
    
    return $changes;
}

function applyChanges( $changes )
{
    global $tempChangesFile, $changesFile, $editor;
    
    file_put_contents( $tempChangesFile, $changes );
    
    // Run the editor
    $descriptors    = [
        ['file', '/dev/tty', 'r'],
        ['file', '/dev/tty', 'w'],
        ['file', '/dev/tty', 'w']
    ];
    $process        = proc_open( "$editor $tempChangesFile", $descriptors, $pipes);
    while(true){
        if ( proc_get_status( $process )['running'] == FALSE ) {
            break;
        }
    }
    // Old Way: Not work
    //exec( "$editor $tempChangesFile" );
    
    
    $oldChanges     = file_exists( $changesFile ) ? file_get_contents( $changesFile ) : '';
    file_put_contents( $tempChangesFile, $oldChanges, FILE_APPEND );
    exec( "mv $tempChangesFile $changesFile" );
}

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
= -h	Display this help. \n
= \n
= -d	Dry-Run: Only display Current Version and last changes without write files. \n
= \n
============================================================================================================================================ \n
";

	return $usage;
}
