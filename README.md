# Laravel CRUD Kit

Laravel CRUD Kit é um pacote para Laravel que gera Model, Controller, Service e
Repository a partir de stubs reutilizáveis.

## Requisitos

- PHP 8.3+
- Laravel 13

## Instalação

Instale o pacote com Composer:

```bash
composer require luizfelipeoliver/laravel-crud-kit
```

O Laravel registra o Service Provider automaticamente via package discovery.

## Uso

Gere os arquivos de CRUD para uma entidade:

```bash
php artisan crud:make User
```

Por padrão, o comando gera:

```text
app/Models/User.php
app/Http/Controllers/Api/UserController.php
app/Services/UserService.php
app/Repositories/UserRepository.php
```

As classes geradas usam estes namespaces por padrão:

```text
App\Models
App\Http\Controllers\Api
App\Services
App\Repositories
```

## Geração Da Model

A Model é gerada inspecionando a tabela do banco que corresponde ao nome da
Model.

Exemplo:

```bash
php artisan crud:make User
```

Nesse caso, o pacote procura a tabela:

```text
users
```

Outros exemplos:

```text
Product  -> products
BlogPost -> blog_posts
```

Quando a tabela existe, a Model gerada inclui:

- atributo `#[Fillable([...])]` com base nas colunas da tabela
- `casts()` com base nos tipos das colunas
- `SoftDeletes` quando a tabela tem a coluna `deleted_at`
- relacionamentos `belongsTo` com base nas foreign keys da tabela
- relacionamentos `hasMany` com base em foreign keys de outras tabelas
- relacionamentos `belongsToMany` com base em tabelas pivot simples

Se o banco não tiver foreign keys definidas, o pacote tenta detectar
relacionamentos usando a convenção de colunas `*_id`.

Exemplo com foreign key:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('title');
    $table->timestamps();
});
```

Ao gerar `Post`, o pacote cria:

```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

Ao gerar `User`, o pacote detecta que `posts.user_id` referencia `users` e
cria:

```php
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}
```

Exemplo de pivot:

```php
Schema::create('role_user', function (Blueprint $table) {
    $table->foreignId('role_id')->constrained();
    $table->foreignId('user_id')->constrained();
});
```

Ao gerar `User`, o pacote cria:

```php
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class, 'role_user');
}
```

A conexão com o banco de dados do Laravel precisa estar configurada antes de
rodar o comando para que campos, casts e relacionamentos sejam detectados.

## Tabela Personalizada

Você só precisa informar `--table` quando a tabela não segue o padrão do
Laravel.

Exemplo:

```bash
php artisan crud:make User --table=system_users
```

Esse comando gera a Model `User`, mas lê os campos da tabela `system_users`.

Se sua tabela segue o padrão do Laravel, use apenas:

```bash
php artisan crud:make User
```

## Configuração

O pacote funciona sem publicar nenhum arquivo. Ele usa a configuração padrão
incluída no próprio pacote.

Para publicar o arquivo de configuração e os stubs no seu projeto Laravel, rode:

```bash
php artisan crud:install
```

Esse comando publica:

```text
config/crud-kit.php
stubs/vendor/crud-kit/
```

Para sobrescrever arquivos já publicados, rode:

```bash
php artisan crud:install --force
```

Você também pode publicar apenas o arquivo de configuração:

```bash
php artisan vendor:publish --tag=crud-kit-config
```

Ou apenas os stubs:

```bash
php artisan vendor:publish --tag=crud-kit-stubs
```

## Caminhos E Namespaces Personalizados

Depois de publicar o arquivo de configuração, edite `config/crud-kit.php` para
customizar onde os arquivos serão gerados e quais namespaces serão usados.

Exemplo:

```php
'paths' => [
    'controllers' => app_path('Http/Controllers/Api'),
    'models' => app_path('Models'),
    'services' => app_path('Services'),
    'repositories' => app_path('Repositories'),
],

'namespaces' => [
    'controllers' => 'App\\Http\\Controllers\\Api',
    'models' => 'App\\Models',
    'services' => 'App\\Services',
    'repositories' => 'App\\Repositories',
],
```

Se um namespace não for configurado, o Laravel CRUD Kit usa o valor padrão.
