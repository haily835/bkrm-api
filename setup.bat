copy .env.example .env
docker-compose build app
docker-compose up -d
docker-compose exec app rm -rf vendor composer.lock
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan storage:link
ECHO BKRM backend running at http://localhost:8000/api