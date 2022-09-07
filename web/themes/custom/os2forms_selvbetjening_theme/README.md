# selvbetjening.aarhuskommune.dk

```sh
docker run --rm --tty --interactive --volume ${PWD}:/app --workdir /app node:16 yarn install
docker run --rm --tty --interactive --volume ${PWD}:/app --workdir /app node:16 yarn build
```
