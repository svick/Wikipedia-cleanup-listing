#!/bin/sh

#$ -j y
#$ -N CleanupListing
#$ -m as
#$ -l sql-s1-user=1
#$ -l h_rt=4:00:00
#$ -l virtual_free=100M
#$ -l arch='*'

php /home/svick/CleanupListing/CreateCleanupListing.php
