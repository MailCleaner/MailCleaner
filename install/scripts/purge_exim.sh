#!/bin/bash

VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi

rm -rf $VARDIR/log/exim_stage1/*
rm -rf $VARDIR/log/exim_stage2/*
rm -rf $VARDIR/log/exim_stage4/*

rm $VARDIR/run/exim*

rm -rf $VARDIR/spool/exim_stage1/db/*
rm -rf $VARDIR/spool/exim_stage1/input/*
rm -rf $VARDIR/spool/exim_stage1/msglog/*

rm -rf $VARDIR/spool/exim_stage2/db/*
rm -rf $VARDIR/spool/exim_stage2/input/*
rm -rf $VARDIR/spool/exim_stage2/msglog/*

rm -rf $VARDIR/spool/exim_stage4/db/*
rm -rf $VARDIR/spool/exim_stage4/input/*
rm -rf $VARDIR/spool/exim_stage4/msglog/*


