# 아주 단순한 Apache+PHP 컨테이너
FROM php:8.2-apache

# Render가 임의 포트를 주입하므로 Apache가 그 포트를 듣게 수정
ENV PORT=10000
RUN sed -ri 's/^Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf && \
    sed -ri 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html

# mbstring 설치는 일단 생략 (필요 없으면 이게 제일 안정적)
# RUN docker-php-ext-install mbstring

EXPOSE 10000
