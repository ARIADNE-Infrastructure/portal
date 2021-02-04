# The ARIADNE portal

### Demo

https://demo.snd.gu.se/ariadne/

### Server
---

- Docker
- Php
- Elasticsearch (remote)

``` bash
# copy settings.json (server/classes dir)
cp settings-example.json settings.json

# install all packages (server dir)
composer install

# run server (localhost port 80, root dir)
docker-compose up

```

### Client
---

- Typescript
- Vue
- Vuex
- Tailwindcss

``` bash
# install all packages (client dir)
npm i

# run the dev server (localhost port 8080, client dir)
npm run dev

# run the dev server over wifi (client dir)
npm run dev-external

# release build (client dir)
npm run build

```

### Deploy to Docker Hub
---

#### Edit settings:

``` bash
/server/classes/settings.json
example: debug: false

/client/webpack.config.js
example: ariadneApiPath  = 'https://localhost:8080/api';
```


#### Create image and publish to Docker Hub

``` bash
# CD to your local Ariadne root folder
cd <ariadne-root>

# Delete client 'dist' and 'node_modules' - Optional but recommended
rm -rf client/dist  (optional)
rm -rf client/node_modules  (optional)

# NPM - install packages and build:
cd client
npm install
npm run build

# Clean /server/html from old client code, if any. Should only contain folder '/api' with backend code.
# Copy client code client/dist to server/html
cd <ariadne-root>
cp -r client/dist/. server/html/

# Docker, build and run:
docker-compose up

# Now you'll have a running container. List containers and copy CONTAINER ID:
docker ps -a

# Create new image from running container with CONTAINER ID:
# (This will create a new image with name 'www-portal-staging' and tag 'latest' )
docker commit <my-container-id> ariadneplusportal/www-portal-staging:latest

# List images and copy name:
docker images

# Login to Docker Hub as user 'ariadneplusportal'
docker login -u ariadneplusportal

# Push image to Docker Hub:
docker push ariadneplusportal/www-portal-staging:latest

```

Your docker image is now public on https://hub.docker.com

DONE!

#### Pull image from Docker Hub and run on localhost:80:
``` bash
docker pull ariadneplusportal/www-portal-staging:latest
docker run -p 80:80 ariadneplusportal/www-portal-staging
```
