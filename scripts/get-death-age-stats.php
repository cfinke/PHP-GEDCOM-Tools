#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:", array( "gedcom:" ) );

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

$ages = array();

foreach ( $entries as $entry ) {
	if ( $entry->block_type != 'INDI' ) {
		continue;
	}
	
	$birth_dates = $entry->getEntrySubValues( 'BIRT', 'DATE' );
	$death_dates = $entry->getEntrySubValues( 'DEAT', 'DATE' );
	
	if ( empty( $birth_dates ) || empty( $death_dates ) ) {
		continue;
	}
	
	$this_persons_age_at_death = strtotime( $death_dates[0] ) - strtotime( $birth_dates[0] );
	$ages[] = round( $this_persons_age_at_death / 60 / 60 / 24 / 365 );
}

$average_age = round( average( $ages ) );

echo "Average age at death: " . $average_age . " years\n";

$median_age = median( $ages );

echo "Median age at death: " . $median_age . " years\n";