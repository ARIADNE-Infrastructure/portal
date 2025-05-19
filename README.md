# The ARIADNE portal

### Hosts running Ariadne-Portal

- https://portal.ariadne-infrastructure.eu/
- https://ariadne-portal-staging.d4science.org/

### Server
---

- Docker
- Php
- OpenSearch

``` bash
# install all packages (server dir)
composer install

# run server (root dir)
docker-compose up

```

### Client
---

- Typescript
- Vue
- Tailwindcss

``` bash
# install all packages (client dir)
npm i

# run the dev server (localhost port 8080, client dir)
npm run dev

# run the dev server over wifi (client dir)
npm run dev-external

# release builds (client dir)
npm run build | build-staging | build-dev | build-local

```

### Deploy to Docker Hub
---

``` bash
# Cd to your local Ariadne root folder
cd <ariadne-root>

# Delete client 'dist' and 'node_modules' - Optional but recommended
rm -rf client/dist

# Npm - install packages and build:
npm --prefix client/ run build-staging
(For Public: npm run build, staging: npm run build-staging, dev: npm run build-dev)

# Clean /server/html from old client code, if any. Should only contain folder '/api' with backend code.
# Copy client code client/dist to server/html
cp -r client/dist/. server/html/

# Docker, build and run:
docker-compose build
docker-compose up

# Now you'll have a running container. On a new terminal window list containers and copy container id:
docker ps -a

# Create new image from running container with CONTAINER ID:
# (This will create a new image with name 'www-portal-staging' and tag 'latest')
docker commit <container-id> ariadneplusportal/www-portal-staging:latest

# List images and copy name:
docker images

# Login to Docker Hub as user 'ariadneplusportal'
docker login -u ariadneplusportal

# Push image to Docker Hub:
docker push ariadneplusportal/www-portal-staging:latest

```

Done! Your docker image is now public on https://hub.docker.com

#### Pull image from Docker Hub and run on localhost:80
``` bash
docker pull ariadneplusportal/www-portal-staging:latest
docker run -p 80:80 ariadneplusportal/www-portal-staging
```
