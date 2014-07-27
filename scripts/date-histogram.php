#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:t:s:h:", array( "gedcom:", "type:", "specificity:", "histogram:" ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['t'] ) ) {
	$cli_options['type'] = $cli_options['t'];
}

if ( isset( $cli_options['t'] ) ) {
	$cli_options['type'] = $cli_options['t'];
}

if ( isset( $cli_options['s'] ) ) {
	$cli_options['specificity'] = $cli_options['s'];
}

if ( empty( $cli_options['specificity'] ) ) {
	$cli_options['specificity'] = 'day';
}

if ( isset( $cli_options['h'] ) ) {
	$cli_options['histogram'] = $cli_options['h'];
}

if ( ! isset( $cli_options['histogram'] ) ) {
	$cli_options['histogram'] = 'X';
}

if ( empty( $cli_options['gedcom'] ) || empty( $cli_options['type'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --type=[BIRT|DEAT|MARR] --specificity=[month|day]\n" );
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

if ( $cli_options['specificity'] == 'day' ) {
	for ( $i = 1; $i <= 365; $i++ ) {
		$date_histogram[ date( "m-d", strtotime( "-" . $i . " days" ) ) ] = 0;
	}
	
	$date_histogram['02-29'] = 0;
	
	$date_format_string = "m-d";
}
else {
	for ( $i = 1; $i <= 12; $i++ ) {
		$date_histogram[ str_pad( $i, 2, "0", STR_PAD_LEFT ) ] = 0;
	}
	
	$date_format_string = "m";
}

foreach ( $entries as $entry ) {
	if ( $cli_options['type'] == 'MARR' && $entry->block_type != 'FAM' ) {
		continue;
	}
	else if ( in_array( $cli_options['type'], array( 'BIRT', 'DEAT' ) ) && $entry->block_type != 'INDI' ) {
		continue;
	}
	
	$this_persons_timestamp = date_to_timestamp( $entry->getEntrySubValue( $cli_options['type'], 'DATE' ), true );
	
	if ( $this_persons_timestamp === false ) {
		continue;
	}
	
	if ( $this_persons_timestamp > strtotime( "-1 day" ) ) {
		continue;
	}
	
	$this_persons_date = date( $date_format_string, $this_persons_timestamp );
	
	$date_histogram[ $this_persons_date ] += 1;
}

print_histogram( $date_histogram, 'ksort', $cli_options['histogram'] );