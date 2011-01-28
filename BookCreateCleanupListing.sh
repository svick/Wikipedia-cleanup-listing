#!/bin/sh

#$ -j y
#$ -N BookCleanupListing
#$ -m e
#$ -l sqlprocs-s1=1

php /home/svick/CleanupListing/BookCreateCleanupListing.php
