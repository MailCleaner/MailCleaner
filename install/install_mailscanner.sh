#!/bin/bash


if [ "$USEDEBS" = "Y" ]; then
  echo -n " installing mailscanner binaries packages...";
  aptitude install mc-mailscanner &> /dev/null
  echo "done.";
else

echo "########!!!!!!!!!!!!##########"
echo " to install mailscanner.. "
echo " 1) install tnef (in /usr/tnef)"
echo " 2) unpack mailscanner archive (the one in perl-tar)"
echo " 3) copy it to /opt/MailScanner"
echo " 4) apply MailScanner.patch to /opt/MailScanner"
echo " 5) cp MailcleanerPrefs.pm and MailWatch.pm in new mailscanner, and apply MailWatch.patch"
echo " 6) /root/compare_ms_configs/compare.pl and compare_language.pl"
echo " ..bye bye..."
exit

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/opt/mailcleaner
        fi
fi

cd $SRCDIR/install/src
tar -xvzf tnef.tar.gz 
cd tnef-1.2.3.1
./configure
make
make install
cd $SRCDIR/install/src
rm -rf tnef-1.2.3.1

cd $SRCDIR/install/src
tar -xvzf Mailscanner.tar.gz
cd MailScanner-install-4.45.4

./install.sh

cd $SRCDIR

if [ -d mailscanner_old ]; then
	rm -rf mailscanner_old
fi
if [ -d mailscanner ]; then
	mv mailscanner mailscanner_old
fi

mv /opt/MailScanner-4.45.4 mailscanner

SD=`echo $SRCDIR | perl -pi -e 's/\//\\\\\//g'`
perl -pi -e "s/\/opt\/MailScanner/$SD\/mailscanner/g" $SRCDIR/mailscanner/bin/check_mailscanner
perl -pi -e "s/config=\S+/config=$SD\/etc\/mailscanner\/MailScanner.conf/g" $SRCDIR/mailscanner/bin/check_mailscanner
perl -pi -e "s/\/opt\/MailScanner/$SD\/mailscanner/g" $SRCDIR/mailscanner/bin/MailScanner
perl -pi -e "s/SCANNERSCONF=\S+/SCANNERSCONF=$SD\/etc\/mailscanner\/virus.scanners.conf/g" $SRCDIR/mailscanner/bin/update_virus_scanners

cp $SRCDIR/install/src/MailScanner_Custom/MailcleanerPrefs.pm $SRCDIR/mailscanner/lib/MailScanner/CustomFunctions/
cp $SRCDIR/install/src/MailScanner_Custom/clamav-wrapper $SRCDIR/mailscanner/lib/
cp $SRCDIR/install/src/MailScanner_Custom/etrust-wrapper $SRCDIR/mailscanner/lib/
cp $SRCDIR/install/src/MailScanner_Custom/etrust-autoupdate $SRCDIR/mailscanner/lib/
cp $SRCDIR/install/src/MailScanner_Custom/MailWatch.pm $SRCDIR/mailscanner/lib/MailScanner/
cp $SRCDIR/install/src/MailScanner_Custom/MailWatch.patch $SRCDIR/mailscanner/lib/MailScanner/
cd $SRCDIR/mailscanner/lib/MailScanner/
patch -p0 <MailWatch.patch

$SRCDIR/bin/dump_mailscanner_config.pl

cd $BACK
fi
