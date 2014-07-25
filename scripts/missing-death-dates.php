#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:a:", array( "gedcom:", "a:", ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['a'] ) ) {
	$cli_options['age'] = $cli_options['a'];
}

if ( empty( $cli_options['gedcom'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --age=[max age]\n" );
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

if ( empty( $cli_options['age'] ) ) {
	$cli_options['age'] = 120;
}

$max_age_in_seconds = $cli_options['age'] * 365 * 24 * 60 * 60;

$missing_deaths = array();

foreach ( $entries as $entry ) {
	if ( $entry->block_type != 'INDI' ) {
		continue;
	}
	
	$death_date = trim( $entry->getEntrySubValue( 'DEAT', 'DATE' ) );
	
	if ( $death_date ) {
		continue;
	}
	
	$birth_date = trim( str_ireplace( array( "abt ", "about " ), "", (string) $entry->getEntrySubValue( 'BIRT', 'DATE' ) ) );
	
	if ( ! $birth_date ) {
		continue;
	}
	
	if ( ! preg_match( '/[0-9]{4}/', $birth_date ) ) {
		// Ensure that there's a year present.
		continue;
	}

	if ( preg_match( "/^[0-9]{4}$/", $birth_date ) ) {
		$birth_date = $birth_date . '-01-01';
	}
	
	$birth_timestamp = strtotime( $birth_date );
	
	if ( $birth_timestamp === false ) {
		continue;
	}
	
	if ( time() - $birth_timestamp > $max_age_in_seconds ) {
		$missing_deaths[ str_replace( "/", "", $entry->getEntryValue( 'NAME' ) ) . ", born " . $entry->getEntrySubValue( 'BIRT', 'DATE' ) ] = time() - $birth_timestamp;
	}
}

arsort( $missing_deaths );

foreach ( $missing_deaths as $key => $age ) {
	echo $key . "\n";
}