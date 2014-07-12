#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:p:o:", array( "gedcom:", "person:", "out:" ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['p'] ) ) {
	$cli_options['person'] = $cli_options['p'];
}

if ( isset( $cli_options['o'] ) ) {
	$cli_options['out'] = $cli_options['o'];
}

if ( empty( $cli_options['gedcom'] ) || empty( $cli_options['person'] ) || empty( $cli_options['out'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --person='Johann /Tuchtenhagen/' --out=/path/to/new-tree.ged\n" );
	die;
}

if ( $cli_options['gedcom']{0} != '/' || $cli_options['out']{0} != '/' ) {
	file_put_contents( 'php://stderr', "Please use absolute file paths.\n" );
	die;
}

$entries = build_gedcom_array( $cli_options['gedcom'] );

if ( false === $entries ) {
	file_put_contents( 'php://stderr', "Couldn't read GEDCOM file: " . $cli_options['gedcom'] . "\n" );
	die;	
}

$branch_head = find_person( $cli_options['person'], $entries );

if ( ! $branch_head ) {
	file_put_contents( 'php://stderr', "Couldn't find branch: " . $cli_options['person'] . "\n" );
	die;
}

// Start buildin' the tree.
$export = array();
$export[ 'HEAD' ] = $entries[ 'HEAD' ];

addPersonAndChildren( $entries[ $branch_head ] );

$output_handle = fopen( $cli_options['out'], 'w' );

if ( ! $output_handle ) {
	file_put_contents( 'php://stderr', "Couldn't open file: " . $cli_options['out'] . "\n" );
	die;
}

foreach ( $export as $entry ) {
	fwrite( $output_handle, implode( "\n", $entry->data ) . "\n" ); 
}

fclose( $output_handle );

echo "GEDCOM written to " . $cli_options['out'] . "\n";

function addPersonAndChildren( $person ) {
	global $export, $entries;
	
	if ( ! isset( $export[ $person->id ] ) ) {
		$export[ $person->id ] = $entries[ $person->id ];
	}
	
	$sources = $person->getRelatedEntries( 'SOUR' );
	
	foreach ( $sources as $source ) {
		if ( ! isset( $export[ $source->id ] ) ) {
			$export[ $source->id ] = $entries[ $source->id ];
			
			$repos = $source->getRelatedEntries( 'REPO' );
			
			foreach ( $repos as $repo ) {
				if ( ! isset( $export[ $repo->id ] ) ) {
					$export[ $repo->id ] = $entries[ $repo->id ];
				}
			}
		}
	}
	
	// Get the families where this person is a spouse.
	$families = $person->getRelatedEntries( 'FAMS' );

	foreach ( $families as $family ) {
		$export[ $family->id ] = $family;
		
		foreach ( array( 'WIFE', 'HUSB' ) as $spouse_type ) {
			$spouses = $family->getRelatedEntries( $spouse_type );
			
			foreach ( $spouses as $spouse ) {
				if ( ! isset( $export[ $spouse->id ] ) ) {
					$export[ $spouse->id ] = $spouse;
				}
			}
		}
		
		$children = $family->getRelatedEntries( 'CHIL' );
		
		foreach ( $children as $child ) {
			addPersonAndChildren( $child );
		}
	}
}