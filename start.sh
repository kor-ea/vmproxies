#!/bin/bash
for f in configs/*; do docker run -d --device=/dev/net/tun:/dev/net/tun --cap-add=NET_ADMIN -v=/root/docker-openvpn-tinyproxy/:/etc/openvpn vm $f; done
docker ps -a | grep 'Exited' | awk '{print $1}' | xargs --no-run-if-empty docker rm

