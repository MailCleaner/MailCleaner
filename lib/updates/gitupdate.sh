#!/bin/bash

CONFFILE=/etc/mailcleaner.conf
SRCDIR=`grep 'SRCDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR="/usr/mailcleaner"
fi

source ${SRCDIR}/lib/lib_utils.sh

# Prevent fetching data when the updater is running
# If starts at the exact same time as the fetcher, there will be an issue with
# the locking mechanism
LOCKFILE_NAME="gitupdate_running"
ret=$(createLockFile ${LOCKFILE_NAME})
if [[ $ret != "0" ]]; then
    echo "Could not create lockfile"
    exit 1
fi
while [[ $(hasFetchersRunning) == 1 ]]; do
    echo "Waiting for all fetchers to be stopped"
    sleep 3
done
sleep 15

git fetch
git ls-files -d | xargs git checkout --

# Check git stash result
git stash save "MC:STASHORNOT"
git pull
git stash list |grep 'MC:STASHORNOT'
[ $? == 1 ] && echo "NoChanges: Nothing to do" && removeLockFile ${LOCKFILE_NAME} && exit 0

res=$(git stash pop | grep 'CONFLICT')
retcod=$?
resparsed=$(echo "$res" |sed -E "s/^CONFLICT.*in\s(.*)/\1/g")
[ $retcod == 1 ] && echo "NoConflict: Nothing to do" && removeLockFile ${LOCKFILE_NAME} && exit 0

OLDIFS=$IFS
IFS=$'\n'
for cffile in $(echo "$resparsed")
do
  echo "> Managing file:"
  echo $cffile
  echo ${cffile}.conflict
  echo "> End of this file"
  mv $cffile ${cffile}.conflict$(date '+_%F_%T')
  git checkout HEAD -- $cffile
done
git stash drop

removeLockFile ${LOCKFILE_NAME} && exit 0
