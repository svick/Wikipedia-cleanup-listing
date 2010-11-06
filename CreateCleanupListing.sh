#!/bin/sh

#$ -N CleanupListing
#$ -m e
#$ -l sqlprocs-s1=1
#$ -l hostname=willow

php /home/svick/cleanup\ listing/CreateCleanupListing.php
