#!/bin/sh

#$ -j y
#$ -N CleanupListing
#$ -m e
#$ -l sql-s1-user=1
#$ -l h_rt=3:00:00
#$ -l virtual_free=100M

php /home/svick/CleanupListing/CreateCleanupListing.php
