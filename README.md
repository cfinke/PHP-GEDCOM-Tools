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

export-related.php
------------------
This will export a new GEDCOM file containing only the person specified and any other people related by blood. This will exclude spouses of blood relatives, but would include, for example, children, grandchildren, cousins, grandparents, and half-siblings.

Usage:

`./scripts/export-related.php --gedcom=/path/to/existing-tree.ged --person='John Doe' --out=/path/to/new-tree.ged

The `--branch` argument must match exactly the name of the person you want the tree based on.

get-death-age-stats.php
-----------------------
This will calculate the average and median age at death for everyone in a GEDCOM file.

Usage:

`./scripts/get-death-age-stats.php --gedcom=/path/to/existing-tree.ged`

Example output:

```
Average age at death: 56 years
Median age at death: 69 years
```

get-gender-count.php
--------------------
This will tally the count of the genders represented in a GEDCOM file.

Usage: 

`./scripts/get-gender-counts.php --gedcom=/path/to/existing-tree.ged`

Example output:

```
M:   1340
F:   1198
```