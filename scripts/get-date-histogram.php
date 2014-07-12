#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:t:", array( "gedcom:", "type:" ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['t'] ) ) {
	$cli_options['type'] = $cli_options['t'];
}

if ( empty( $cli_options['gedcom'] ) || empty( $cli_options['type'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --type=[BIRT|DEAT]\n" );
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

$date_histogram = array();

for ( $i = 1; $i < 366; $i++ ) {
	$date_histogram[ date( "m-d", strtotime( "-" . $i . " days" ) ) ] = 0;
}

$date_histogram['02-29'] = 0;

foreach ( $entries as $entry ) {
	if ( $entry->block_type != 'INDI' ) {
		continue;
	}
	
	$dates = $entry->getEntrySubValues( $cli_options['type'], 'DATE' );
	
	if ( empty( $dates ) ) {
		continue;
	}
	
	if ( ! $dates[0] ) {
		continue;
	}
	
	$dates[0] = trim( $dates[0] );
	
	if ( strpos( $dates[0], " " ) === false ) {
		continue;
	}
	
	$this_persons_timestamp = strtotime( $dates[0] );
	
	if ( $this_persons_timestamp > strtotime( "-1 day" ) ) {
		continue;
	}
	
	if ( $this_persons_timestamp === false ) {
		file_put_contents( 'php://stderr', "Couldn't parse date: " . $dates[0] . "\n" );
		continue;
	}
	
	$this_persons_date = date( "m-d", $this_persons_timestamp );
	
	$date_histogram[ $this_persons_date ] += 1;
}

ksort( $date_histogram );

foreach ( $date_histogram as $date => $count ) {
	echo $date . "\t" . str_repeat( 'X', $count ) . "\n";
}
