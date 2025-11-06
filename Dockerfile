# Apache 포함된 PHP 8.2 이미지
FROM php:8.2-apache

# 작업 디렉토리
WORKDIR /var/www/html

# 소스 복사 (포크 레포 전체)
COPY . /var/www/html

# mbstring 등 필요한 확장 (라이브러리에서 멀티바이트 문자열 사용 가능성 대비)
RUN docker-php-ext-install mbstring

# Apache가 80 포트로 구동됨
EXPOSE 80
