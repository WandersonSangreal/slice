# Processador de Arquivos EP747 e .JSON

Este projeto é um processador de arquivos de transações que lê arquivos JSON em massa e arquivos EP747 TXT, processa os
dados e os armazena em um banco de dados PostgresSQL.

---

## Arquivos

O arquivo `process.php` executa as migrations `src/Migrations` ou truncate caso as tabelas já existam, e executa a
chamada dos processadores tanto o clearing, quanto o ep747. Ambos estão na pasta `src/Processors`, também é injetado o
Serviço de inserção dos dados no banco `src/Services`.

---

## Consultas

As consultas, estão dentro da pasta `src/Queries` com os valores dos resultados, alguns valores infelizmente não bateram
mas tentei deixar o mais próximo possível.

---

## Preparar o Projeto

1. Clone o repositório:
   ```
   git clone https://github.com/wandersonsangreal/slice
   ```
2. Versão ideal do php
    ```
    php8.2+
    ```

2. Instale as dependências:
    ```
    composer install
    ```

3. Configuração do arquivo `.env` com base no `.env.example`, as unicas informações necessárias são as informações do
   banco e do banco de teste. No exemplo é utilizado o postgres

    ```
    DB_NAME=slice
    DB_USER=postgres
    DB_PASS=password
    DB_HOST=127.0.0.1
    ```

   ```
    DB_NAME=slice_test
    DB_USER=postgres
    DB_PASS=password
    DB_HOST=127.0.0.1
    ```
   Existe outra configuração que é o `STREAM_BYTES`, que define o tamanho do stream de dados a ser processado, está
   definido com o tamanho de `1,5MB` (aproximadamente 1300 linhas), esse é o tamanho limite para não atingir a limitação
   de parâmetros do bind da inserção (65535). Existem outras maneiras de copiar os dados que poderiam ser exploradas
   como o COPY e sem utilizar o bind, mas essa matem o bindValues uma exatidão maior nos dados


4. Antes de executar qualquer comando o arquivos devem estar em suas respectivas pastas `file/ep747` e `file/json`, após
   os arquivos serem processados eles irão para as subpastas processed, ou failed em caso de falha.


5. Essas pastas precisam de permissão, leitura, execução e escrita

---

## Rodar o Projeto

1. Basta executar o comando
    ```
    php process.php
    ```

2. Caso queira rodar os testes (verifique os *arquivos*, eles também devem estar nas pastas files)
    ```
    ./vendor/bin/phpunit tests --colors
    ```

3. Outra forma de rodar o projeto é através do docker, o processo roda automaticamente e os resultados podem ser
   conferidos no banco de dados
    ```
    docker compose up -d
    ```
    ```
    docker exec -it db bash
    ```

---
