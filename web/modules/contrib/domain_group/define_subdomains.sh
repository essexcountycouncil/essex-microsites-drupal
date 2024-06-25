#!/bin/bash

declare -a HOSTS=(${CONTAINER_NAME} 'example.com')
declare -a SUBDOMAINS=('group-a1' 'group-a2' 'group-a3' 'group-b1' 'group-b2' 'group-b3')

for HOST in ${HOSTS[@]}; do
    echo '127.0.0.1' ${HOST} >> /etc/hosts

    for SUBDOMAIN in ${SUBDOMAINS[@]}; do
      echo '127.0.0.1' ${SUBDOMAIN}.${HOST} >> /etc/hosts
      echo '127.0.0.1' ${SUBDOMAIN}.example.${HOST} >> /etc/hosts
    done
done
