#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:a:", array( "gedcom:", ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( empty( $cli_options['gedcom'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged\n" );
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
	
	$children = get_related_children( $entry );
	
	$birth_timestamp = date_to_timestamp( $entry->getEntrySubValue( 'BIRT', 'DATE' ) );
	$death_timestamp = date_to_timestamp( $entry->getEntrySubValue( 'DEAT', 'DATE' ) );
	
	foreach ( $children as $child ) {
		$child_birth_timestamp = date_to_timestamp( $child->getEntrySubValue( 'BIRT', 'DATE' ) );
		
		if ( $child_birth_timestamp ) {
			if ( $birth_timestamp && ( $child_birth_timestamp < $birth_timestamp ) ) {
				echo $child->getEntryValue( 'NAME' ) . " (" . $child->getEntrySubValue( 'BIRT', 'DATE' ) . ") was born before their parent, " . $entry->getEntryValue( 'NAME' ) . " (" . $entry->getEntrySubValue( 'BIRT', 'DATE' ) . ")\n";
			}
			else if ( $death_timestamp && ( $entry->getEntryValue( 'SEX' ) == 'F' && $child_birth_timestamp > $death_timestamp ) ) {
				echo $child->getEntryValue( 'NAME' ) . " (" . $child->getEntrySubValue( 'BIRT', 'DATE' ) . ") was born after their mother, " . $entry->getEntryValue( 'NAME' ) . ", died (" . $entry->getEntrySubValue( 'DEAT', 'DATE' ) . ")\n";
			}
			else if ( $death_timestamp && ( $entry->getEntryValue( 'SEX' ) == 'M' && $child_birth_timestamp > ( $death_timestamp + ( 9 * 30 * 24 * 60 * 60 ) ) ) ) {
				echo $child->getEntryValue( 'NAME' ) . " (" . $child->getEntrySubValue( 'BIRT', 'DATE' ) . ") was born more than 9 months after their father, " . $entry->getEntryValue( 'NAME' ) . ", died (" . $entry->getEntrySubValue( 'DEAT', 'DATE' ) . ")\n";
			}
			else if ( $birth_timestamp && ( $child_birth_timestamp < ( $birth_timestamp + ( 15 * 365 * 24 * 60 * 60 ) ) ) ) {
				echo $child->getEntryValue( 'NAME' ) . " (" . $child->getEntrySubValue( 'BIRT', 'DATE' ) . ") was born before their parent, " . $entry->getEntryValue( 'NAME' ) . ", turned 15 (born " . $entry->getEntrySubValue( 'BIRT', 'DATE' ) . ")\n";
			}
			else if ( $entry->getEntryValue( 'SEX' ) == 'F' && $birth_timestamp && ( $child_birth_timestamp > ( $birth_timestamp + ( 50 * 365 * 24 * 60 * 60 ) ) ) ) {
				echo $child->getEntryValue( 'NAME' ) . " (" . $child->getEntrySubValue( 'BIRT', 'DATE' ) . ") was born after their mother, " . $entry->getEntryValue( 'NAME' ) . ", turned 50 (born " . $entry->getEntrySubValue( 'BIRT', 'DATE' ) . ")\n";
			}
		}
	}
}