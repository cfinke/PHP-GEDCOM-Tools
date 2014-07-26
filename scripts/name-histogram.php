#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:s:", array( "gedcom:", "sex:" ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['s'] ) ) {
	$cli_options['sex'] = $cli_options['s'];
}

if ( empty( $cli_options['sex'] ) ) {
	$cli_options['sex'] = false;
}

if ( empty( $cli_options['gedcom'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --sex=[M|F]\n" );
	die;
}

if ( $cli_options['gedcom']{0} != '/' ) {
	file_put_contents( 'php://stderr', "Please use absolute file paths.\n" );
	die;
}

$entries = build_gedcom_array( $cli_options['gedcom'] );

if ( false === $entries ) {
	file_put_contents( 'php://stderr', "Couldn't read GEDCOM file: " . $cli_options['gedcom'] . "\n" );
	die;	
}

$names = array();

foreach ( $entries as $entry ) {
	if ( $entry->block_type != 'INDI' ) {
		continue;
	}
	
	if ( $cli_options['sex'] ) {
		$sex = $entry->getEntryValue( 'SEX' );
		
		if ( ! $sex || $sex != $cli_options['sex'] ) {
			continue;
		}
	}
	
	foreach ( $entry->getEntryValues( 'NAME' ) as $full_name ) {
		$first_and_middle = trim( preg_replace( "#/[^/]+/#", "", $full_name ) );
		$separated_names = explode( " ", $first_and_middle );
		
		foreach ( $separated_names as $separated_name ) {
			$separated_name = str_replace( ".", "", $separated_name );
			
			$separated_name = trim( $separated_name );
			
			if ( strlen( $separated_name ) <= 1 ) {
				continue;
			}
			
			$separated_name = ucfirst( strtolower( $separated_name ) );
			
			if ( in_array( $separated_name, array( "Baby", "Boy", "Girl", "Unknown", "II", "III", "IV", "Jr", "Sr" ) ) ) {
				continue;
			}
			
			if ( ! isset( $names[ $separated_name ] ) ) {
				$names[ $separated_name ] = 0;
			}
			
			$names[ $separated_name ] += 1;
		}
	}
}

arsort( $names );

$longest_name = 0;

foreach ( $names as $name => $count ) {
	$longest_name = max( strlen( $name ), $longest_name );
}

foreach ( $names as $name => $count ) {
	echo str_pad( $name, $longest_name + 1 ) . str_repeat( 'X', $count ) . "\n";
}
