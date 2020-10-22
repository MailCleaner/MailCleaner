#!/bin/bash

FLAGS=$`grep flags -m1 /proc/cpuinfo | cut -d ":" -f 2 | tr [:upper:] [:lower:]`
declare -a ARR=()
for flag in $FLAGS
do
    case "$flag" in
    "sse4_1" | "sse4_2" | "ssse3" | "fma" | "cx16" | "popcnt" | "avx" | "avx2")
        ARR+=($flag)
        ;;
    *)
        ;;
    esac
done
IFS=$'\n' sorted=($(sort <<<"${ARR[*]}")); unset IFS
for i in "${sorted[@]}"; do
   list+="$i-"
done
echo "cpu: ${list::
