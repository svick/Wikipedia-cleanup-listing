#!/bin/sh

#$ -N CleanupListing
#$ -m e
#$ -l sqlprocs-s1=1

php /home/svick/CleanupListing/CreateCleanupListing.php
