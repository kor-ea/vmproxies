#!/bin/bash
echo 100000 >> /proc/sys/kernel/keys/root_maxkeys; echo 2500000 >> /proc/sys/kernel/keys/root_maxbytes
docker-compose build
CNT=10000
prefix="configs/"
for f in configs/*; do CNT=$[CNT+1]; NAME=${f#$prefix}; docker-compose run -d --name=$NAME --rm vpn $f; done
docker ps -a | grep 'Exited' | awk '{print $1}' | xargs --no-run-if-empty docker rm
