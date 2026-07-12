FROM debian
RUN apt update && apt install --yes php-cli php-xml composer
RUN mkdir /app
WORKDIR /app
