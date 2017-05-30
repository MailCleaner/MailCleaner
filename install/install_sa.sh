#!/bin/sh

SAVERSION=3.4.0
RULESVERSION=3.4.0.r1565117
BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi
if [ "$VARDIR" = "" ]; then
        VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "VARDIR" = "" ]; then
                VARDIR=/var/mailcleaner
        fi
fi

cd $SRCDIR/install/src

cd $SRCDIR/install/src
tar -xvjf Mail-SpamAssassin.tar.bz2
cd Mail-SpamAssassin-$SAVERSION
echo "root@localhost" | perl Makefile.PL 2>&1
make 2>&1
#make test 2>&1
make install 2>&1

## work around pre-perl 5.8 bytes encoding bug
if [ -f /usr/local/share/perl/5.8.4/Mail/SpamAssassin/Message.pm ]; then
  RES=`grep 'use bytes;' /usr/local/share/perl/5.8.4/Mail/SpamAssassin/Message.pm`
  if [ "$RES" = "" ]; then
    echo "work around pre-perl 5.8 bytes encoding bug"
    perl -pi -e 's/use warnings;\n/use warnings;\nuse bytes;\n/' /usr/local/share/perl/5.8.4/Mail/SpamAssassin/Message.pm 
  fi
fi

rm -rf $VARDIR/spool/spamassassin/bayes

cd $BACK
rm -rf $SRCDIR/install/src/Mail-SpamAssassin-$SAVERSION

cp $SRCDIR/install/src/Mail-SpamAssassin-rules.tar.gz $SRCDIR/install/src/Mail-SpamAssassin-rules-$RULESVERSION.tar.gz 2>&1
cp $SRCDIR/install/src/Mail-SpamAssassin-rules.tgz.sha1 $SRCDIR/install/src/Mail-SpamAssassin-rules-$RULESVERSION.tar.gz.sha1 2>&1
#cp $SRCDIR/install/src/Mail-SpamAssassin-rules.tgz.asc $SRCDIR/install/src/Mail-SpamAssassin-rules-$RULESVERSION.tar.gz.asc 2>&1
/usr/local/bin/sa-update --nogpg --install $SRCDIR/install/src/Mail-SpamAssassin-rules-$RULESVERSION.tar.gz 2>&1
rm $SRCDIR/install/src/Mail-SpamAssassin-rules-$RULESVERSION.tar.gz 2>&1
rm $SRCDIR/install/src/Mail-SpamAssassin-rules-$RULESVERSION.tar.gz.sha1 2>&1
#rm $SRCDIR/install/src/Mail-SpamAssassin-rules-$RULESVERSION.tar.gz.asc 2>&1

/usr/local/bin/sa-compile 2>&1

touch $VARDIR/spool/spamassassin/bayes_seen 2>&1
chown -R mailcleaner:mailcleaner $VARDIR/spool/spamassassin/bayes_* 2>&1


