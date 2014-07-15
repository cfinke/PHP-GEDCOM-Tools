#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:t:", array( "gedcom:", "type:", ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['t'] ) ) {
	$cli_options['type'] = $cli_options['t'];
}

if ( isset( $cli_options['t'] ) ) {
	$cli_options['type'] = $cli_options['t'];
}

if ( empty( $cli_options['gedcom'] ) || empty( $cli_options['type'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --type=[DEAT|MARR]\n" );
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

$histogram = array();

for ( $i = 0; $i <= 120; $i++ ) {
	$histogram[ $i ] = 0;
}

$this_morning = strtotime( date( "Y-m-d 00:00:00" ) );

foreach ( $entries as $entry ) {
	if ( $cli_options['type'] == 'MARR' && $entry->block_type != 'FAM' ) {
		continue;
	}
	else if ( $cli_options['type'] == 'DEAT' && $entry->block_type != 'INDI' ) {
		continue;
	}
	
	$date = trim( str_ireplace( array( "abt ", "about " ), "", (string) $entry->getEntrySubValue( $cli_options['type'], 'DATE' ) ) );
	
	if ( ! $date ) {
		continue;
	}
	
	$date = trim( $date );
	
	if ( strpos( $date, " " ) === false ) {
		continue;
	}
	
	if ( preg_match( "/^[0-9]{4}$/", $date ) ) {
		$date = $date . '-01-01';
	}

	
	if ( ! preg_match( '/[0-9]{4}/', $date ) ) {
		// Ensure that there's a year present.
		continue;
	}
	
	$timestamp = strtotime( $date );
	
	if ( $timestamp === false ) {
		file_put_contents( 'php://stderr', "Couldn't parse date: " . $date . "\n" );
		continue;
	}
	
	if ( $timestamp > $this_morning ) {
		continue;
	}
	
	if ( $cli_options['type'] == 'DEAT' ) {
		$people_ids = array( $entry->id );
	}
	else {
		$people_ids = array( $entry->getEntryValue( 'HUSB' ), $entry->getEntryValue( 'WIFE' ) );
	}
	
	foreach ( $people_ids as $person_id ) {
		if ( ! $person_id ) {
			continue;
		}
		
		if ( ! isset( $entries[ $person_id ] ) ) {
			continue;
		}
		
		$person = $entries[ $person_id ];
		
		// Now, check date against BIRT DATE
		$birth_date = trim( str_ireplace( array( "abt ", "about " ), "", (string) $person->getEntrySubValue( 'BIRT', 'DATE' ) ) );

		if ( preg_match( "/^[0-9]{4}$/", $birth_date ) ) {
			$birth_date = $birth_date . '-01-01';
		}
		
		if ( ! preg_match( '/[0-9]{4}/', $birth_date ) ) {
			// Ensure that there's a year present.
			continue;
		}

		if ( ! $birth_date ) {
			continue;
		}
		
		$birth_timestamp = strtotime( $birth_date );
	
		if ( $birth_timestamp > $this_morning ) {
			continue;
		}

		if ( false === $birth_timestamp ) {
			continue;
		}
	
		$histogram_value = round( ( $timestamp - $birth_timestamp ) / 60 / 60 / 24 / 365 );
	
		$histogram[ $histogram_value ] += 1;
	}
}

ksort( $histogram );

foreach ( $histogram as $value => $count ) {
	echo $value . "\t" . str_repeat( 'X', $count ) . "\n";
}
