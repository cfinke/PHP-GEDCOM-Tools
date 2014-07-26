#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:s:t:", array( "gedcom:", "sex:", "timeframe:" ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['s'] ) ) {
	$cli_options['sex'] = $cli_options['s'];
}

if ( empty( $cli_options['sex'] ) ) {
	$cli_options['sex'] = false;
}

if ( isset( $cli_options['t'] ) ) {
	$cli_options['timeframe'] = $cli_options['t'];
}

if ( empty( $cli_options['timeframe'] ) ) {
	$cli_options['timeframe'] = false;
}

if ( empty( $cli_options['gedcom'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --sex=[M|F]\n --timeframe=[year|decade]" );
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

$years = array();
$ages = array();
$this_morning = strtotime( date( "Y-m-d 00:00:00" ) );

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
	
	$key = date( 'Y', $death_timestamp );
	
	if ( $cli_options['timeframe'] && $cli_options['timeframe'] == 'decade' ) {
		$key = floor( $key / 10 ) * 10;
	}
	
	$years[ $key ][] = round( $this_persons_age_at_death / 60 / 60 / 24 / 365 );
}

ksort( $years );

echo "Year\tAverage\tMedian\n";

foreach ( $years as $year => $ages ) {
	echo $year . "\t" . round( average( $ages ) ) . "\t" . median( $ages ) . "\n";
}
