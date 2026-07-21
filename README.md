# SignFlow

Sistema de gerenciamento de processos digitais e assinaturas eletrônicas desenvolvido com **Laravel 13**, **Livewire V4**, **Alpine.js** e **Tailwind CSS**.

---

## 🚀 Requisitos

- Docker
- Docker Compose
- Git

---

## 📥 Clonando o projeto

```bash
git clone https://github.com/higorch/signflow.git

cd signflow
```

---

## 🐳 Inicializando o ambiente

Suba todos os containers.

```bash
docker compose up -d
```

> **Importante:** aguarde aproximadamente **7 minutos** para que todos os serviços sejam inicializados corretamente (MySQL, PHP-FPM, Nginx, Mailpit e demais dependências).

---

## ⚙️ Configuração

Acesse o container da aplicação.

```bash
docker exec -it app bash
```

Instale as dependências e gere a chave da aplicação.

```bash
composer install

php artisan key:generate
```

> O arquivo `.env` já acompanha o projeto configurado para execução em Docker.

---

## 🗄️ Banco de dados

Popular apenas os dados de demonstração:

```bash
php artisan db:seed
```

Ou recriar completamente o banco:

```bash
php artisan migrate:fresh --seed
```

Os seeders geram automaticamente:

- Usuários
- Departamentos
- Categorias
- Processos
- Signatários
- Arquivos
- Demais dados necessários para testes

---

## ⚙️ Processamento da fila

Em um novo terminal execute:

```bash
php artisan queue:work
```

O worker é responsável pelo processamento dos Jobs da aplicação, incluindo o envio dos e-mails.

---

## 🧪 Fluxo sugerido para testes

1. Acesse utilizando um dos usuários abaixo.
2. Crie um novo processo.
3. Salve o processo como **Rascunho**.
4. Edite o processo e clique em **Enviar para Assinatura**.
5. Todos os signatários serão notificados por e-mail.
6. Retorne o processo para **Rascunho**.
7. Os signatários receberão uma nova notificação informando que o processo voltou para rascunho.

> Todos os e-mails enviados podem ser visualizados no Mailpit. Mantenha o `php artisan queue:work` em execução durante os testes.

---

## 👥 Usuários de teste

| Perfil | E-mail | Senha |
|--------|--------|--------|
| Cliente | higor@mail.test | password |
| Cliente | guilherme@mail.test | password |
| Signatário | haaland@mail.test | password |
| Signatário | vozinha@mail.test | password |
| Signatário | maria@mail.test | password |

---

## 🌐 Acesso

| Serviço | Endereço |
|----------|----------|
| Aplicação | http://localhost:8029 |
| Mailpit | http://localhost:8030 |

---

## 🔌 Portas

| Serviço | Porta |
|----------|------:|
| Aplicação (Nginx) | 8029 |
| Mailpit | 8030 |
| MySQL | 3329 |

---

## 🛠️ Tecnologias

### Backend

- PHP 8.3+
- Laravel 13
- MySQL

### Frontend

- Livewire V4
- Livewire Single File Components (SFC)
- Alpine.js
- Tailwind CSS

### Processamento de arquivos

#### PDFs

Utiliza **Ghostscript** para:

- Compressão
- Otimização
- Redução do tamanho dos arquivos

#### Imagens

Utiliza **ImageMagick** para:

- Correção automática da orientação (EXIF)
- Conversão para WebP
- Compressão
- Otimização
- Aplicação automática de marca d'água

---

## 🐳 Comandos úteis

Entrar no container:

```bash
docker exec -it app bash
```

Parar os containers:

```bash
docker compose down
```

Reconstruir os containers:

```bash
docker compose up -d --build
```

---

## 📌 Observações

- Aguarde aproximadamente **7 minutos** após subir os containers antes de iniciar a configuração do projeto.
- O arquivo `.env` já está configurado para o ambiente Docker.
- Para validar o envio de e-mails, mantenha o `php artisan queue:work` em execução.
- Todos os e-mails podem ser visualizados em **http://localhost:8030**.
- Todos os dados utilizados nos testes são fictícios e gerados automaticamente pelos seeders.
- O projeto foi preparado para execução integral via Docker.