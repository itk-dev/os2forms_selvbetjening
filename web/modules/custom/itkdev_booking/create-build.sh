docker-compose exec node npm run build
cp -v assets/build/static/js/main.*.js assets/dist/main.js
cp -v assets/build/static/css/main.*.css assets/dist/main.css
