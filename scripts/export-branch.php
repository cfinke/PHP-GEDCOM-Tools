#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:b:o:", array( "gedcom:", "branch:", "out:" ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['b'] ) ) {
	$cli_options['branch'] = $cli_options['b'];
}

if ( isset( $cli_options['o'] ) ) {
	$cli_options['out'] = $cli_options['o'];
}

if ( empty( $cli_options['gedcom'] ) || empty( $cli_options['branch'] ) || empty( $cli_options['out'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --branch='Johann /Tuchtenhagen/' --out=/path/to/new-tree.ged\n" );
	die;
}

if ( $cli_options['gedcom']{0} != '/' || $cli_options['out']{0} != '/' ) {
	file_put_contents( 'php://stderr', "Please use absolute file paths.\n" );
	die;
}

// Read in the existing GEDCOM and build a comprehensive list of entries.
$handle = fopen( $cli_options['gedcom'], "r" );

$entries = array();

$last_entry = array();
$last_entry_id = null;

while ( ! feof( $handle ) && $line = fgets( $handle ) ) {
	$line = trim( $line );
	if ( substr( $line, 0, 2 ) == '0 ' ) {
		if ( $last_entry_id ) {
			$entries[ $last_entry_id ] = new GEDCOM_Entry( implode( "\n", $last_entry ) );
			$last_entry = array();
		}
		
		$line_parts = explode( " ", $line, 3 );
		$last_entry_id = $line_parts[1];
	}
	
	$last_entry[] = $line;
}

$entries[ $last_entry_id ] = new GEDCOM_Entry( implode( "\n", $last_entry ) );

$branch_head = null;
$possible_branch_heads = array();

foreach ( $entries as $entry_id => $entry ) {
	if ( in_array( $cli_options['branch'], $entry->getEntryValues( "NAME" ) ) ) {
		$possible_branch_heads[] = $entry_id;
	}
}

fclose( $handle );

if ( count( $possible_branch_heads ) == 1 ) {
	$branch_head = $possible_branch_heads[0];
}
else if ( count( $possible_branch_heads ) > 1 ) {
	echo count( $possible_branch_heads ) . " possible branches found.\n";
	
	foreach ( $possible_branch_heads as $possible_branch_head ) {
		echo "Did you mean this individual?\n\t" . implode( "\n\t", $entries[ $possible_branch_head ]->data ) . "\n[y/n] ";
		$input = get_input();
		
		if ( strtolower( $input ) == "y" ) {
			$branch_head = $possible_branch_head;
			break;
		}
	}
}

if ( ! $branch_head ) {
	file_put_contents( 'php://stderr', "Couldn't find branch: " . $cli_options['branch'] . "\n" );
	die;
}

// Start buildin' the tree.
$export = array();
$export[ 'HEAD' ] = $entries[ 'HEAD' ];
$relevant_families = array();

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