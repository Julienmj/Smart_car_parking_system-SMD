web: vendor/bin/heroku-php-apache2 public/

# Keep the app alive by pinging it every 10 minutes
worker: while true; do curl -s https://your-app-name.herokuapp.com/health-check.php > /dev/null || true; sleep 600; done
