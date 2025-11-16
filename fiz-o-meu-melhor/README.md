# Sistema de Importação de Arquivos

Sistema desenvolvido em Laravel para importação e processamento de arquivos CSV e Excel, com armazenamento em MongoDB e processamento assíncrono através de filas RabbitMQ.

## 📋 Índice

- [🚀 Início Rápido](#-início-rápido)
- [Sobre o Projeto](#sobre-o-projeto)
- [Tecnologias Utilizadas](#tecnologias-utilizadas)
- [Arquitetura](#arquitetura)
- [Pré-requisitos](#pré-requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Executando o Projeto](#executando-o-projeto)
- [Autenticação](#autenticação)
- [Endpoints da API](#endpoints-da-api)
- [Documentação Swagger](#documentação-swagger)
- [Sistema de Filas](#sistema-de-filas)
- [Testes](#testes)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Docker](#docker)

## 🚀 Início Rápido

Para rodar a aplicação, use o script `start.sh` (presumindo que você está usando Linux):
Se estiver usando windows, não sei o que fazer haha (Sério nunca usei com docker)
Se estiver MAC piorou pois sou pobre!

```bash
bash start.sh
```

O script irá:
- Subir os containers Docker
- Executar as migrations
- Configurar o ambiente

Depois disso, basta chamar os endpoints de upload enviando o arquivo no campo `file`.

Como o sistema usa **RabbitMQ** como fila, ele roda com cron e comando. O bash já inicia o worker, porém você pode executar manualmente este comando para processar importações sem esperar pelo worker:

```bash
docker exec -it app-laravel php artisan file-imports:consume
```

**Dúvidas?** Acesse a documentação interativa:
- **Swagger**: http://localhost:8080/api/documentation

---

## 🎯 Sobre o Projeto

Este projeto é um sistema de importação de arquivos que permite:

- Upload de arquivos CSV e Excel
- Validação de arquivos duplicados através de hash MD5
- Processamento assíncrono de arquivos em background
- Armazenamento de dados processados no MongoDB
- Busca e consulta de dados importados
- Histórico completo de uploads com status de processamento
- Documentação interativa via Swagger

## 🛠 Tecnologias Utilizadas

- **Laravel 10** - Framework PHP
- **PHP 8.1+** - Linguagem de programação
- **PostgreSQL** - Banco de dados relacional (histórico de uploads)
- **MongoDB** - Banco de dados NoSQL (dados importados)
- **RabbitMQ** - Sistema de filas para processamento assíncrono
- **Redis** - Cache e sessões
- **JWT Auth** - Autenticação via tokens
- **L5-Swagger** - Documentação da API
- **PHPUnit** - Testes unitários e de integração
- **Docker** - Containerização
- **Nginx** - Servidor web
- **PhpSpreadsheet** - Processamento de arquivos Excel

## 🏗 Arquitetura

O sistema segue uma arquitetura em camadas com os seguintes componentes:

```
┌─────────────────┐
│   API Client    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Controllers   │ (FileImportController, SearchImportedDataController)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    Services     │ (FileImportService, ImportService, QueuesService)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Repositories  │ (UploadHistoricRepository, MongoRepository)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Databases     │ (PostgreSQL + MongoDB)
└─────────────────┘

         │
         ▼
┌─────────────────┐
│  RabbitMQ Queue │ (Processamento assíncrono)
└─────────────────┘
```

### Fluxo de Processamento

1. **Upload**: Cliente envia arquivo via API
2. **Validação**: Sistema verifica se arquivo já foi importado (hash MD5)
3. **Armazenamento**: Arquivo é salvo no PostgreSQL (tabela `issodeviaserums3`)
4. **Fila**: Upload é adicionado à fila RabbitMQ
5. **Processamento**: Worker consome mensagem da fila
6. **Parse**: Arquivo é parseado (CSV ou Excel)
7. **Armazenamento**: Dados são salvos no MongoDB
8. **Status**: Status do upload é atualizado (WAITING → PROCESSING → PROCESSED/ERROR)

## 📦 Pré-requisitos

- Docker e Docker Compose
- Git
- Acesso à porta 8080 (API), 8081 (Swagger), 5433 (PostgreSQL), 27017 (MongoDB), 5672 (RabbitMQ), 15672 (RabbitMQ Management)

## 🚀 Instalação

### 1. Clone o repositório

```bash
git clone <url-do-repositorio>
cd desafio-desenvolvedor/fiz-o-meu-melhor
```

### 2. Execute o script de inicialização

```bash
chmod +x start.sh
./start.sh
```

O script `start.sh` irá:
- Copiar `.env.example` para `.env`
- Subir todos os containers Docker
- Ajustar permissões da pasta `storage`
- Instalar dependências do Composer
- Executar migrations e seeders
- Criar banco de dados de testes
- Iniciar o agendador de tarefas

### 3. Configure o JWT Secret (se necessário)

```bash
docker exec -it app-laravel php artisan jwt:secret
```

## ⚙️ Configuração

### Variáveis de Ambiente

O arquivo `.env` contém as principais configurações:

```env
# Aplicação
APP_NAME="File Import API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Banco de Dados PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=appdb
DB_USERNAME=root
DB_PASSWORD=root

# MongoDB
MONGO_URI=mongodb://mongo:27017
MONGO_DATABASE=file_imports
MONGO_COLLECTION=cadastro_instrumentos

# RabbitMQ
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=root
RABBITMQ_PASSWORD=root

# JWT
JWT_SECRET=<gerado-automaticamente>

# Queue
QUEUE_CONNECTION=rabbitmq
```

## 🏃 Executando o Projeto

### Iniciar os containers

```bash
docker compose up -d
```

### Executar migrations

```bash
docker exec -it app-laravel php artisan migrate --seed
```

### Iniciar o worker de processamento

O worker consome mensagens da fila RabbitMQ e processa os arquivos:

```bash
docker exec -it app-laravel php artisan file-imports:consume
```

**Nota**: Em produção, recomenda-se executar o worker como um serviço ou usar um process manager como Supervisor.

### Gerar documentação Swagger

```bash
docker exec -it app-laravel php artisan l5-swagger:generate
```

## 🔐 Autenticação

O sistema utiliza autenticação JWT (JSON Web Token). Todas as rotas da API requerem autenticação.

### Token de Teste

Para facilitar os testes, utilize o seguinte token JWT:

```
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwODAiLCJpYXQiOjE3NjMyMjIxMzUsImV4cCI6MjA3ODU4MjEzNSwibmJmIjoxNzYzMjIyMTM1LCJqdGkiOiJYQUVhbXppcFVETHdoTW91Iiwic3ViIjoiMSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.eunicEiAzuRnWchU6FxzWMIu8DGl9gBBiY2ZUxTaHqQ
```

### Como usar o token

Inclua o token no header `Authorization` de todas as requisições:

```bash
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Exemplo com cURL

```bash
curl -X GET "http://localhost:8080/api/uploads" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwODAiLCJpYXQiOjE3NjMyMjIxMzUsImV4cCI6MjA3ODU4MjEzNSwibmJmIjoxNzYzMjIyMTM1LCJqdGkiOiJYQUVhbXppcFVETHdoTW91Iiwic3ViIjoiMSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.eunicEiAzuRnWchU6FxzWMIu8DGl9gBBiY2ZUxTaHqQ"
```

## 📡 Endpoints da API

### Base URL

```
http://localhost:8080/api
```

### 1. Upload de Arquivo

**POST** `/uploads`

Envia um arquivo CSV ou Excel para importação.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (form-data):**
- `file`: Arquivo CSV ou Excel (.csv, .xls, .xlsx)

**Resposta de Sucesso (201):**
```json
{
  "id": 1,
  "name": "data.csv",
  "hash": "abc123def456",
  "status": "WAITING",
  "reference_date": "2024-01-01"
}
```

**Respostas de Erro:**
- `401`: Não autenticado
- `409`: Arquivo já foi importado anteriormente
- `422`: Erro de validação

**Exemplo:**
```bash
curl -X POST "http://localhost:8080/api/uploads" \
  -H "Authorization: Bearer {token}" \
  -F "file=@/caminho/para/arquivo.csv"
```

### 2. Histórico de Uploads

**GET** `/uploads`

Retorna a lista de todos os uploads realizados.

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
[
  {
    "id": 1,
    "name": "data.csv",
    "hash": "abc123",
    "status": "PROCESSED",
    "reference_date": "2024-01-01"
  }
]
```

**Status possíveis:**
- `WAITING`: Aguardando processamento
- `PROCESSING`: Em processamento
- `PROCESSED`: Processado com sucesso
- `ERROR`: Erro no processamento

### 3. Buscar Dados Importados

**GET** `/data`

Busca dados importados do MongoDB filtrando por ticker e data do relatório.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `TckrSymb` (obrigatório): Símbolo do ticker (ex: PETR4, AMZO34)
- `RptDt` (obrigatório): Data do relatório no formato YYYY-MM-DD
- `page` (opcional): Número da página (padrão: 1)
- `per_page` (opcional): Itens por página (padrão: 50, máximo: 500)

**Resposta de Sucesso (200):**
```json
[
  {
    "RptDt": "2024-08-22",
    "TckrSymb": "AMZO34",
    "MktNm": "EQUITY-CASH",
    "SctyCtgyNm": "BDR",
    "ISIN": "BRAMZOBDR002",
    "CrpnNm": "AMAZON.COM, INC"
  }
]
```

**Exemplo:**
```bash
curl -X GET "http://localhost:8080/api/data?TckrSymb=PETR4&RptDt=2024-08-22&page=1&per_page=50" \
  -H "Authorization: Bearer {token}"
```

## 📚 Documentação Swagger

A documentação interativa da API está disponível através do Swagger UI.

### Acessar Swagger

```
http://localhost:8081
```

Ou através da rota da aplicação:

```
http://localhost:8080/api/documentation
```

### Recursos do Swagger

- Visualização de todos os endpoints
- Teste direto dos endpoints na interface
- Esquemas de requisição e resposta
- Autenticação JWT integrada
- Exemplos de uso

### Atualizar Documentação

Após alterar anotações OpenAPI nos controllers, gere a documentação:

```bash
docker exec -it app-laravel php artisan l5-swagger:generate
```

## 🔄 Sistema de Filas

O sistema utiliza RabbitMQ para processamento assíncrono de arquivos.

### Configuração

A fila é configurada em `config/services.php`:

```php
'rabbitmq' => [
    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'root'),
    'password' => env('RABBITMQ_PASSWORD', 'root'),
    'queue' => env('RABBITMQ_QUEUE', 'file-imports'),
]
```

### RabbitMQ Management

Interface web para gerenciar filas e mensagens:

```
http://localhost:15672
```

**Credenciais:**
- Usuário: `root`
- Senha: `root`

### Worker Command

O comando `file-imports:consume` consome mensagens da fila:

```bash
php artisan file-imports:consume
```

**Funcionamento:**
1. Declara a fila `file-imports`
2. Consome uma mensagem por vez
3. Processa o arquivo associado ao `upload_id`
4. Atualiza status do upload
5. Salva documentos no MongoDB
6. Confirma (ACK) ou rejeita (NACK) a mensagem

### Monitoramento

Para monitorar o processamento em tempo real, execute o worker em um terminal separado:

```bash
docker exec -it app-laravel php artisan file-imports:consume
```

## 🧪 Testes

O projeto possui uma suíte completa de testes unitários e de integração.

### Executar Testes

```bash
docker exec -it app-laravel php artisan test
```

Ou usando PHPUnit diretamente:

```bash
docker exec -it app-laravel vendor/bin/phpunit
```

### Cobertura de Testes

O projeto possui **77.4% de cobertura de código**.

#### Cobertura por Componente

| Componente | Cobertura |
|------------|-----------|
| Controllers | 88.9% - 100% |
| Services | 56.1% - 100% |
| Repositories | 31.0% - 100% |
| Models | 0% - 100% |
| Parsers | 80.6% - 86.7% |
| Commands | 93.3% |

### Gerar Relatório de Cobertura

```bash
docker exec -it app-laravel vendor/bin/phpunit --coverage-html tests/Coverage
```

O relatório HTML estará disponível em:
```
tests/Coverage/index.html
```

### Estrutura de Testes

```
tests/
├── Feature/          # Testes de integração
│   ├── Controllers/
│   ├── Repositories/
│   ├── Routes/
│   └── Services/
└── Unit/             # Testes unitários
    ├── Console/
    ├── Exceptions/
    ├── Models/
    ├── Repositories/
    └── Services/
```

## 📁 Estrutura do Projeto

```
fiz-o-meu-melhor/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── FileImportWorkerCommand.php    # Worker de processamento
│   ├── Exceptions/
│   │   └── FileAlreadyImportedException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── FileImportController.php           # Upload e histórico
│   │   │       └── SearchImportedDataController.php   # Busca de dados
│   │   └── Middleware/
│   ├── Models/
│   │   ├── IssoDeviaSerUmS3.php      # Armazenamento de arquivos
│   │   ├── UploadHistoric.php        # Histórico de uploads
│   │   └── User.php                  # Model de usuário
│   ├── Repositories/
│   │   ├── MongoRepository.php
│   │   ├── MongoRepositoryInterface.php
│   │   ├── UploadHistoricRepository.php
│   │   └── UploadHistoricRepositoryInterface.php
│   └── Services/
│       ├── Contracts/                # Interfaces dos serviços
│       ├── FileImportService.php     # Serviço de upload
│       ├── ImportService.php         # Serviço de importação
│       ├── QueuesService.php         # Serviço de filas
│       └── Parsers/                  # Parsers de arquivos
│           ├── AbstractFileParser.php
│           ├── CsvFileParser.php
│           ├── ExcelFileParser.php
│           ├── FileParserInterface.php
│           ├── FileParserResolver.php
│           └── FileParserResolverInterface.php
├── config/
│   ├── jwt.php           # Configuração JWT
│   ├── l5-swagger.php    # Configuração Swagger
│   ├── mongo.php         # Configuração MongoDB
│   └── queue.php         # Configuração de filas
├── database/
│   ├── migrations/       # Migrations do banco
│   ├── seeders/          # Seeders
│   └── factories/        # Factories para testes
├── routes/
│   └── api.php           # Rotas da API
├── tests/                # Testes
├── docker-compose.yaml   # Configuração Docker
├── Dockerfile            # Imagem Docker
└── start.sh              # Script de inicialização
```

## 🐳 Docker

O projeto utiliza Docker Compose para orquestração de containers.

### Containers

- **app-laravel**: Aplicação PHP-FPM
- **nginx**: Servidor web
- **postgres-db**: Banco PostgreSQL
- **mongo**: Banco MongoDB
- **rabbitmq**: Sistema de filas
- **redis-cache**: Cache Redis
- **swagger-editor**: Editor Swagger (opcional)

### Comandos Úteis

**Ver logs:**
```bash
docker compose logs -f app
```

**Reiniciar containers:**
```bash
docker compose restart
```

**Parar containers:**
```bash
docker compose down
```

**Parar e remover volumes:**
```bash
docker compose down -v
```

**Acessar container:**
```bash
docker exec -it app-laravel bash
```

### Portas

| Serviço | Porta |
|---------|-------|
| API | 8080 |
| Swagger | 8081 |
| PostgreSQL | 5433 |
| MongoDB | 27017 |
| RabbitMQ | 5672 |
| RabbitMQ Management | 15672 |
| Redis | 6379 |

## 📊 Banco de Dados

### PostgreSQL

Armazena o histórico de uploads e metadados.

**Tabelas principais:**
- `upload_historic`: Histórico de uploads
- `issodeviaserums3`: Armazenamento de arquivos (base64)
- `users`: Usuários do sistema

### MongoDB

Armazena os dados processados dos arquivos importados.

**Database:** `file_imports`
**Collection:** `cadastro_instrumentos`

**Estrutura de documento:**
```json
{
  "RptDt": "2024-08-22",
  "TckrSymb": "AMZO34",
  "MktNm": "EQUITY-CASH",
  "SctyCtgyNm": "BDR",
  "ISIN": "BRAMZOBDR002",
  "CrpnNm": "AMAZON.COM, INC",
  "upload_id": "abc123"
}
```

## 🔍 Validações

### Arquivos Suportados

- **CSV**: `.csv` (delimitadores: `;` ou `,`)
- **Excel**: `.xlsx`, `.xlsm`, `.xls`

### Colunas Obrigatórias

Os arquivos devem conter as seguintes colunas:
- `TckrSymb`: Símbolo do ticker
- `RptDt`: Data do relatório

### Validações Implementadas

- Verificação de arquivo duplicado (hash MD5)
- Validação de extensão de arquivo
- Validação de colunas obrigatórias
- Validação de formato de dados
- Tratamento de encoding UTF-8

## 🚨 Tratamento de Erros

O sistema possui tratamento robusto de erros:

- **FileAlreadyImportedException**: Arquivo já importado (409)
- **Validação de dados**: Erros de validação (422)
- **Erros de processamento**: Status ERROR no histórico
- **Logs**: Todos os erros são registrados em logs

## 📝 Notas Importantes

1. **Tamanho máximo de upload**: 200MB (configurado no PHP)
2. **Processamento assíncrono**: Arquivos são processados em background
3. **Idempotência**: Arquivos duplicados são detectados via hash MD5
4. **Worker**: Execute o worker em produção para processar arquivos
5. **Token JWT**: O token fornecido é válido até 2026 (para testes)

## 🤝 Contribuindo

## 📄 Licença

Proibido o uso desse código por gatinho, o desenvolvedor não apoia o trabalho felino por troca de saches, eles devem ganhar saches de graça!.

---

**Desenvolvido com ❤️ usando Laravel**
