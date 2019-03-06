#!/bin/bash

cd "$(dirname "$0")" # go to script dir

file="${2}${3}";

status=`cat "${file}"`;

parts=(${status//|/ })

hostname="${parts[0]}";
external_port="${parts[1]}";
internal_port="${parts[2]}";
extra="${parts[3]}";

echo "$hostname $external_port $internal_port $extra";

if [ "${extra,,}" == "delete" ]; then
	control="-D"; # delete
else
	control="-A"; # add
fi

/sbin/iptables -t nat "${control}" PREROUTING -p tcp --dport "${internal_port}" -j DNAT --to-destination "${hostname}:${external_port}";
/sbin/iptables -t nat "${control}" POSTROUTING -p tcp -d "${hostname}" --dport "${external_port}" -j SNAT --to-source "$(hostname -i | awk '{print $NF}')"
