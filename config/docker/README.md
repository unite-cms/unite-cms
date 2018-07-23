unite cms docker 
================

## Prerequisites

Install docker and docker compose

## Usage on OSX
    on osx we have to use a nfs share, otherwise it's painfully slow
    
    # copy over docker env file
    cp .env.docker .env
    
    # set the following env vars
    CONTAINER_DIR=/app/unite-cms // project folder inside container
    SOURCE_DIR=/private/var/www/unite-cms // unite-cms project dir on your computer

    # run shellscript to set up nfs share 
    bash config/docker/setup-nfs-osx.sh 
    
    # build the images
    docker-compose build
    
    # bring up the containers in detached mode
    docker-compose up -d
    
    # on first run (with local php installed)
    bin/console assets:install
    bin/console doctrine:schema:update --force
    
    # on first run (without local php)
    docker exec unitecms-web /app/unite-cms/bin/console assets:install
    docker exec unitecms-web /app/unite-cms/bin/console doctrine:schema:update --force
    
    # check localhost
    http://localhost:8080

### Usage on general linux (centos, ubuntu etc.)

    # copy over docker env file
    cp .env.docker .env
    
    # edit docker-compose.yml
    
      comment the following lines
    
      #nfsmount:
      #  driver: local
      #  driver_opts:
      #    type: nfs
      #    o: addr=host.docker.internal,rw,nolock,hard,nointr,nfsvers=3
      #    device: ":${SOURCE_DIR}"
      
      use the normal mount option instead of nfs mount in unitecms-web image
         # mount on a general linux
         - "./:/app/unite-cms" 

    # build the images
    docker-compose build
    
    # bring up the containers in detached mode
    docker-compose up -d
    
    # on first run (with local php installed)
    bin/console assets:install
    bin/console doctrine:schema:update --force
    
    # on first run (without local php)
    docker exec unitecms-web /app/unite-cms/bin/console assets:install
    docker exec unitecms-web /app/unite-cms/bin/console doctrine:schema:update --force
    
    # check localhost
    http://localhost:8080

### Useful docker commands

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