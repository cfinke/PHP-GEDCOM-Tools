#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:", array( "gedcom:", ) );

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

$histogram = array();

foreach ( $entries as $entry ) {
	if ( $entry->block_type != 'INDI' ) {
		continue;
	}
	
	$children = get_related_children( $entry, $entries );
	$children_count = count( $children );
	
	if ( ! isset( $histogram[ $children_count ] ) ) {
		$histogram[ $children_count ] = 1;
	}
	else {
		$histogram[ $children_count ] += 1;
	}
}

$most_children = max( array_keys( $histogram ) );

for ( $i = 1; $i < $most_children; $i++ ) {
	if ( ! isset( $histogram[ $i ] ) ) {
		$histogram[ $i ] = 0;
	}
}

if ( isset( $histogram[ 0 ] ) ) {
	unset( $histogram[ 0 ] );
}

ksort( $histogram );

foreach ( $histogram as $value => $count ) {
	echo $value . "\t" . str_repeat( 'X', $count ) . "\n";
}
