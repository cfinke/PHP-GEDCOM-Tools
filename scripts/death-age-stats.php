#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:a::s::", array( "gedcom:", "age::", "sex::" ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['a'] ) ) {
	$cli_options['age'] = $cli_options['a'];
}

if ( isset( $cli_options['s'] ) ) {
	$cli_options['sex'] = $cli_options['s'];
}

if ( empty( $cli_options['sex'] ) ) {
	$cli_options['sex'] = false;
}

if ( empty( $cli_options['gedcom'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --age=[minimum age at death] --sex=[M|F]\n" );
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

$ages = array();
$this_morning = strtotime( date( "Y-m-d 00:00:00" ) );
$minimum_age = ( $cli_options['age'] ? $cli_options['age'] * 365 * 24 * 60 * 60 : -1 );


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
	
	$birth_date = trim( str_ireplace( array( "abt ", "about " ), "", (string) $entry->getEntrySubValue( 'BIRT', 'DATE' ) ) );
	$death_date = trim( str_ireplace( array( "abt ", "about " ), "", (string) $entry->getEntrySubValue( 'DEAT', 'DATE' ) ) );
	
	if ( preg_match( "/^[0-9]{4}$/", $birth_date ) ) {
		$birth_date = $birth_date . '-01-01';
	}

	if ( preg_match( "/^[0-9]{4}$/", $death_date ) ) {
		$death_date = $death_date . '-01-01';
	}
	
	if ( ! $birth_date || ! $death_date ) {
		continue;
	}
	
	$death_timestamp = strtotime( $death_date );
	$birth_timestamp = strtotime( $birth_date );
	
	if ( $death_timestamp > $this_morning || $birth_timestamp > $this_morning ) {
		continue;
	}
	
	if ( false === $death_timestamp || false === $birth_timestamp ) {
		continue;
	}
	
	$this_persons_age_at_death = $death_timestamp - $birth_timestamp;
	
	if ( $this_persons_age_at_death > $minimum_age ) {
		$ages[] = round( $this_persons_age_at_death / 60 / 60 / 24 / 365 );
	}
}

$average_age = round( average( $ages ) );

echo "Average age at death: " . $average_age . " years\n";

$median_age = median( $ages );

echo "Median age at death: " . $median_age . " years\n";