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
	
	$death_note = $entry->getEntryValue( 'DEAT' );
	$words = explode( " ", preg_replace( "/[^a-z\- \n]/", "", strtolower( $death_note ) ) );
	
	$words = array_unique( $words );
	
	foreach ( $words as $word ) {
		$word = trim( $word );
		
		if ( ! $word ) {
			continue;
		}
		
		if ( in_array( $word, array( "in", "of", "the", "and", "a", "an", "by", "on", "died", "age", "killed", "his", "her", "mr", "mrs", "he", "she", "nee", ) ) ) {
			continue;
		}
		
		if ( ! isset( $all_words[ $word ] ) ) {
			$all_words[ $word ] = 0;
		}
		
		$all_words[ $word ] += 1;
	}
}

print_histogram( $all_words, 'arsort' );