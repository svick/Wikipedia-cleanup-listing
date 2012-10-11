mkfifo archive_pipe
gzip < archive_pipe > $3 &
mysqldump --compact -th sql-s1-user u_svick_cleanup $1 -w "$2" > archive_pipe
result=$?
wait
rm archive_pipe
exit $result
