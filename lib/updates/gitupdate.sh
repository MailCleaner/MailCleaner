#!/bin/bash

git fetch
git ls-files -d | xargs git checkout --

# Check git stash result
git stash save "MC:STASHORNOT"
git pull
git stash list |grep 'MC:STASHORNOT'
[ $? == 1 ] && echo "NoChanges: Nothing to do" && exit 0

res=$(git stash pop | grep 'CONFLICT')
retcod=$?
resparsed=$(echo "$res" |sed -E "s/^CONFLICT.*in\s(.*)/\1/g")
[ $retcod == 1 ] && echo "NoConflict: Nothing to do" && exit 0

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
