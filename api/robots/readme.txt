# Tunnel Service

# to install this service:

apt-get install incron

if [ `cat /etc/incron.allow | grep "root"` -lt 1 ]; echo "root" >> /etc/incron.allow

echo "/var/www/untrobotics/api/robots/tunnel-status/ IN_MODIFY /var/www/untrobotics/api/robots/update-iptables.sh $$ $@ $# $% $&" > /tmp/incrontab
incrontab /tmp/incrontab