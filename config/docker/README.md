unite cms docker 
================

## Installation

Install docker and docker compose

### Usage with docker compose

    # clone repo and go to root folder

    # build the images
    docker-compose build

    # bring up the containers in detached mode
    docker-compose up -d

    # check localhost
    http://localhost:8080

### Useful commands

    # prune all local images
    docker system prune -a

    # all in one command, close containers, rebuild them and start them
    docker-compose down && docker-compose build --no-cache && docker-compose up -d

    # go inside one of the containers
    docker exec -it <mycontainer> bash
    
    # go inside one of the containers with root permissions
    docker exec -u 0 -it <mycontainer> bash

    # list the running containers
    docker ps

    # shutdown containers
    docker-compose down

    # bring up the containers, debugging mode
    docker-compose up