#! /bin/bash
CONF_FILE="/usr/mailcleaner/share/spamassassin/mails_without_LOM"
RULE_FILE="/usr/mailcleaner/share/spamassassin/93_mails_without_LOM.cf"

# Remove the rule file
if [ -e $RULE_FILE ]; then
        rm $RULE_FILE
fi

# Dont do anything if the feature is not activated
if [ ! -f $CONF_FILE ]; then
        exit
fi

# Remove spaces and empty lines
sed -i 's/\s*//g' $CONF_FILE
sed -i '/^$/d' $CONF_FILE

# We dont consider empty files
if [[ ! -s $CONF_FILE ]]; then
        exit
fi
sort -u $CONF_FILE -o $CONF_FILE

# Entries counter
count=0
echo "# Please dont edit this file as it is overwritten by /usr/mailcleaner/bin/dump_custom_spamc_rules.sh" > $RULE_FILE
# We look for each entry in To header
for i in `cat $CONF_FILE`; do
        echo "header __DOMAIN_$count To =~ /$i/i" >> $RULE_FILE
        ((count++))
done

# Meta for all entries
echo -n "meta __DOMAINS_NO_MONEY  ( __DOMAIN_0 " >> $RULE_FILE
recount=1
while [ $recount -lt $count ]; do
        echo -n "+ __DOMAIN_$recount " >> $RULE_FILE
        ((recount++))
done
echo ") >= 1" >> $RULE_FILE
sed -i 's/\@/\\\@/g' $RULE_FILE

# Meta to remove the LOTS_OF_MONEY rule
echo "meta DOMAIN_NO_MONEY ( LOTS_OF_MONEY && __DOMAINS_NO_MONEY )" >> $RULE_FILE
echo "score DOMAIN_NO_MONEY -2.0" >> $RULE_FILE
