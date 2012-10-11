#!/bin/sh

#$ -j y
#$ -N CleanUnfinishedRuns
#$ -m e
#$ -l sql-s1-user=1
#$ -l h_rt=0:30:00
#$ -l virtual_free=25M
#$ -l arch='*'

php /home/svick/CleanupListing/CleanUnfinishedRuns.php
