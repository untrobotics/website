# Tunnel Service

## to install this service:

	apt-get install incron

	if [ `cat /etc/incron.allow | grep "root"` -lt 1 ]; echo "root" >> /etc/incron.allow

	echo "/var/www/untrobotics/api/robots/tunnel-status/ IN_MODIFY /var/www/untrobotics/api/robots/update-iptables.sh \$\$ \$@ \$# \$% \$&" > /tmp/incrontab
	incrontab /tmp/incrontab

## how it works

The tunnelling service receives POST requests from NAT'd machines, these requests include an `ngrok` URL, and endpoint name. It then creates/updated a tunnel on the system from one of the external server's port to the NAT'd machine's SSH port. The internal port mapping is set up in the `tunnel_api_keys` table.