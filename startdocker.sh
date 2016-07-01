#!/bin/bash
docker-compose build
for f in configs/*.ovpn; do docker-compose run -d --rm vpn $f; done
docker ps -a | grep 'Exited' | awk '{print $1}' | xargs --no-run-if-empty docker rm
