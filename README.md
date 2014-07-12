PHP-GEDCOM-Tools
================

Tools for interacting with GEDCOM files. These have only been tested on a very small sample of GEDCOM files, but maybe they'll come in handy for you.

export-branch.php
-----------------
This will export a new GEDCOM file containing only the person specified, their descendants and descendants' spouses, and any events or other entries related to those people.

This is handy for when you have a massive family tree constructed but would like to share only a portion of it with someone else.

Usage:

`./scripts/export-branch.php --gedcom=/path/to/existing-tree.ged --person='John Doe' --out=/path/to/new-tree.ged`

The `--person` argument must match exactly the name of the person you want at the top of the tree.

