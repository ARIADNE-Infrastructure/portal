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
