# Laravel Kraken Generator

Gerador de arquivos para Laravel baseado em stubs e blueprints. A proposta do
pacote é gerar arquivos seguindo um padrão de projeto sem prender o núcleo a um
CRUD específico.

Na configuração inicial, o pacote gera uma arquitetura em camadas com:

- Model
- Controller
- Service
- Repository
- Route

Opcionalmente, com `--test`, também gera:

- Factory
- Feature test

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

Gere os arquivos para uma entidade:

```bash
php artisan kraken:make User
```

Por padrão, o blueprint usado é `api`. O comando acima gera:

```text
app/Models/User.php
app/Http/Controllers/Api/UserController.php
app/Services/UserService.php
app/Repositories/UserRepository.php
routes/api.php
```

Ao concluir, o comando informa os arquivos criados, os arquivos ignorados por
já existirem e o tempo total de execução. Se a geração falhar, o comando exibe
um erro no terminal e retorna falha.

## Blueprints

O pacote possui dois blueprints iniciais:

```text
api
web
```

### API

O blueprint `api` gera um controller preparado para respostas JSON.

```bash
php artisan kraken:make User --api
```

### Web

O blueprint `web` gera um controller preparado para Inertia.js. O pacote apenas
gera os arquivos; ele não instala nem configura Inertia no projeto.

```bash
php artisan kraken:make User --web
```

## Gerar Apenas Um Arquivo

Use `--only` para gerar somente um tipo de arquivo:

```bash
php artisan kraken:make User --only=model
php artisan kraken:make User --only=controller
php artisan kraken:make User --only=service
php artisan kraken:make User --only=repository
```

Valores aceitos:

```text
model
controller
service
repository
```

Quando `--only=controller` é usado, o Kraken também gera a rota correspondente,
porque a rota faz parte da superfície do controller.

## Gerar Teste Feature

Use `--test` para gerar também uma factory e um teste Feature básico:

```bash
php artisan kraken:make User --test
```

No blueprint `api`, isso gera:

```text
database/factories/UserFactory.php
tests/Feature/Api/UserControllerTest.php
```

O teste gerado valida o endpoint `index` usando a rota nomeada do resource:

```php
$this->getJson(route('users.index'))
    ->assertOk();
```

No blueprint `web`, o teste é gerado em:

```text
tests/Feature/Web/UserControllerTest.php
```

A factory só é gerada quando `--test` é informado. As rotas são geradas junto
com o controller por padrão.

## Repository

O tipo padrão de repository é `simple`:

```bash
php artisan kraken:make User --repository=simple
```

Também é possível gerar repository com carregamento de relações detectadas no
banco:

```bash
php artisan kraken:make User --repository=relations
```

Nesse caso, quando existirem relações detectadas, o repository gerado usa:

```php
->with(['posts', 'roles'])
```

## Geração Da Model

A Model é gerada inspecionando a tabela do banco que corresponde ao nome da
Model.

Exemplo:

```bash
php artisan kraken:make User
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
- atributo `#[Table(...)]`
- atributo `#[WithoutTimestamps]` quando a tabela não tem timestamps
- `casts()` com base nos tipos das colunas
- `SoftDeletes` quando a tabela tem a coluna `deleted_at`
- relacionamentos `belongsTo` com base nas foreign keys da tabela

Se o banco não tiver foreign keys definidas, o pacote tenta detectar
relacionamentos usando a convenção de colunas `*_id`.

A conexão com o banco de dados do Laravel precisa estar configurada antes de
rodar o comando para que campos, casts e relacionamentos sejam detectados.

### Relacionamentos

Na versão atual, o Kraken gera automaticamente apenas relacionamentos
`belongsTo`, porque eles são os mais seguros de inferir pela tabela atual.

Exemplo:

```text
posts.user_id     -> Post::user()
posts.category_id -> Post::category()
```

Relacionamentos inversos e pivots não são gerados automaticamente por enquanto:

```text
User::posts()
Category::posts()
```

Esse comportamento é intencional. O Kraken só gera muitos-para-muitos quando a
intenção está declarada explicitamente em uma migration com `#[Pivot(...)]`.

Exemplo:

```php
use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['post', 'tag'])]
class {};
```

Com uma tabela `post_tag` seguindo a convenção do Laravel e contendo as foreign
keys esperadas, o Kraken pode gerar:

```php
/**
 * @return BelongsToMany<Tag, $this>
 */
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class);
}
```

Convenções planejadas:

```text
foreign key: singular_id
tabelas comuns: plural
tabelas pivot: singular_singular em ordem alfabética
```

Exemplos de pivots válidas:

```text
post_tag
role_user
category_product
```

Tabelas com colunas de negócio, como `posts`, `orders` e `invoices`, não devem
ser tratadas como pivot.

O atributo `#[BelongsToMany(...)]` existe no pacote, mas ainda não é conectado
na V1. Para muitos-para-muitos, use `#[Pivot(...)]`.

## Tabela Personalizada

Você só precisa informar `--table` quando a tabela não segue o padrão do
Laravel.

```bash
php artisan kraken:make User --table=system_users
```

Esse comando gera a Model `User`, mas lê os campos da tabela `system_users`.

## Configuração

O pacote funciona sem publicar nenhum arquivo. Ele usa a configuração padrão
incluída no próprio pacote.

Para publicar o arquivo de configuração e os stubs no projeto Laravel, rode:

```bash
php artisan kraken:install
```

Esse comando publica:

```text
config/generator.php
stubs/vendor/kraken/
```

Para sobrescrever arquivos já publicados:

```bash
php artisan kraken:install --force
```

Você também pode publicar apenas o arquivo de configuração:

```bash
php artisan vendor:publish --tag=kraken-config
```

Ou apenas os stubs:

```bash
php artisan vendor:publish --tag=kraken-stubs
```

## Caminhos E Namespaces Personalizados

Depois de publicar o arquivo de configuração, edite `config/generator.php`.

Exemplo:

```php
return [
    'default_blueprint' => 'api',

    'paths' => [
        'api_controller' => app_path('Http/Controllers/Api'),
        'web_controller' => app_path('Http/Controllers'),
        'models' => app_path('Models'),
        'services' => app_path('Services'),
        'repositories' => app_path('Repositories'),
        'factories' => database_path('factories'),
        'api_tests' => base_path('tests/Feature/Api'),
        'web_tests' => base_path('tests/Feature/Web'),
        'api_routes' => base_path('routes/api.php'),
        'web_routes' => base_path('routes/web.php'),
    ],

    'namespaces' => [
        'api_controller' => 'App\\Http\\Controllers\\Api',
        'web_controller' => 'App\\Http\\Controllers',
        'models' => 'App\\Models',
        'services' => 'App\\Services',
        'repositories' => 'App\\Repositories',
        'factories' => 'Database\\Factories',
    ],

    'repository' => [
        'default' => 'simple',
    ],

    'relationships' => [
        'default' => 'belongs_to',

        'conventions' => [
            'foreign_key' => 'singular_id',
            'pivot_table' => 'alphabetical_singular',
        ],
    ],
];
```

## Stubs Personalizados

Depois de publicar os stubs, você pode editar os arquivos em:

```text
stubs/vendor/kraken/
```

A estrutura padrão é:

```text
stubs/vendor/kraken/
├── api/
│   └── controller.stub
├── database/
│   └── factory.stub
├── routes/
│   ├── api-resource.stub
│   └── web-resource.stub
├── shared/
│   ├── model.stub
│   ├── repository.stub
│   ├── repository-relations.stub
│   └── service.stub
├── tests/
│   ├── api-feature.stub
│   └── web-feature.stub
└── web/
    └── controller.stub
```

O gerador procura primeiro pelos stubs publicados no projeto. Se não encontrar,
usa os stubs internos do pacote.
