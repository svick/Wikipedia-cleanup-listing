#!/bin/sh

#$ -j y
#$ -N ArchiveOldListings
#$ -m e
#$ -l sql-s1-user=1
#$ -l h_rt=6:00:00
#$ -l virtual_free=100M
#$ -l arch='*'

php /home/svick/CleanupListing/ArchiveOldListings.php
