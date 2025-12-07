
FROM php:8.2-cli


RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       sqlite3 \
       libsqlite3-dev \
       pkg-config \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*


WORKDIR /app


COPY . /app


EXPOSE 8000


CMD ["php", "-S", "0.0.0.0:8000", "-t", "/app"]
