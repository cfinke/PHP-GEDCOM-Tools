PHP-GEDCOM-Tools
================

Tools for interacting with GEDCOM (family tree) files. These have only been tested on a very small sample, but maybe they'll come in handy for you.

export-branch.php
-----------------
Export a new GEDCOM file containing only the person specified, their descendants and descendants' spouses, and any events or other entries related to those people.

This is handy for when you have a massive family tree constructed but would like to share only a portion of it with someone else.

Usage:

`./scripts/export-branch.php --gedcom=/path/to/existing-tree.ged --person='John Doe' --out=/path/to/new-tree.ged`

The `--person` argument must match exactly the name of the person you want at the top of the tree.

export-related.php
------------------
Export a new GEDCOM file containing only the person specified and any other people related by blood. This will exclude spouses of blood relatives, but would include, for example, children, grandchildren, cousins, grandparents, and half-siblings.

Usage:

`./scripts/export-related.php --gedcom=/path/to/existing-tree.ged --person='John Doe' --out=/path/to/new-tree.ged`

The `--branch` argument must match exactly the name of the person you want the tree based on.

death-age-stats.php
-------------------
Calculate the average and median age at death for everyone in a GEDCOM file.

Usage:

`./scripts/death-age-stats.php --gedcom=/path/to/existing-tree.ged --age=[minimum age at death] --sex=[M|F]`

The optional `--age` parameter can be used to specify a minimum age at death to find, for example, the median age at death of all family members that didn't die before the age of 30.

The optional `--sex` parameter can be used to filter the results by gender.

Example output:

```
Average age at death: 56 years
Median age at death: 69 years
```

gender-count.php
----------------
Tally the count of the genders represented in a GEDCOM file.

Usage: 

`./scripts/gender-counts.php --gedcom=/path/to/existing-tree.ged`

Example output:

```
M:   1340
F:   1198
```

date-histogram.php
------------------
Print a histogram of date (month/day) frequencies for a given event type.

Usage: 

`./scripts/date-histogram.php --gedcom=/path/to/existing-tree.ged --type=BIRT`

Supported `--type` values are `BIRT` (birth) and `DEAT` (death).

Example output:

```
01-01   XX
01-02   X
01-03   X
01-04   XX
01-05   XX
01-06   XX
01-07   XXXXX
01-08   XXXX
01-09   
01-10   XXX
01-11   XXXX
01-12   XXX
[...]
12-20   XXXXX
12-21   XXXX
12-22   XXXXXXX
12-23   XX
12-24   XXXX
12-25   
12-26   X
12-27   
12-28   XX
12-29   XXXX
12-30   XXX
12-31   X
```
name-histogram.php
------------------
Generate a histogram of name frequency in a GEDCOM file.

Usage:

`./scripts/name-histogram.php --gedcom=/path/to/existing-tree.ged --sex=[M|F]`

The optional `--sex` parameter can be used to filter the results by gender.

Example output:

```
Marie        XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
Ann          XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
John         XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
Lee          XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
Michael      XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
William      XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
[...]
Evelyn       XXX
Wilma        XX
Adele        XX
Reka         X
Laurel       X
Friedericke  X
```
