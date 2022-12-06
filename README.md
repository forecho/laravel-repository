# Laravel Repository

[![Latest Version on Packagist](https://img.shields.io/packagist/v/forecho/laravel-repository.svg?style=flat-square)](https://packagist.org/packages/forecho/laravel-repository)
[![Total Downloads](https://img.shields.io/packagist/dt/forecho/laravel-repository.svg?style=flat-square)](https://packagist.org/packages/forecho/laravel-repository)
![GitHub Actions](https://github.com/forecho/laravel-repository/actions/workflows/main.yml/badge.svg)

The Laravel Repository package is meant to be a generic repository implementation for Laravel.

## Installation

You can install the package via composer:

```bash
composer require forecho/laravel-repository
```

## Usage

Create a repository class:

```php
<?php 
namespace App\Repositories;

use App\Models\Post;
use Forecho\LaravelRepository\Repository;

class PostRepository extends Repository
{
    // Optional
    public array $partialMatchAttributes = ['title'];

    // Optional
    public array $defaultOrder = ['created_at' => 'desc'];
    
    // Optional
    public array $booleanAttributes = ['status'];

    public string $modelClass = Post::class;

    // Optional
    public function boot()
    {
        $this->pushCriteria(UserCriteriaCriteria::class);
    }

}
```

Use the repository in your controller:

```php
<?php
namespace App\Http\Controllers;

use App\Repositories\PostRepository;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    protected PostRepository $repository;

    public function __construct(PostRepository $accountRepository)
    {
        $this->repository = $accountRepository;
    }
   
    public function index(Request $request, PostRepository $userRepository)
    {
        $posts = $this->repository->search($request->all())->paginate();
        // $posts = $this->repository->with(['user'])->search($request->all())->paginate();
        
        return response()->json(UserResource::collection($posts);
    }
}
```

## Criteria

Create a criteria class:

```php
<?php
namespace App\Criteria;

use App\Services\UserService;
use Forecho\LaravelRepository\Contracts\CriteriaInterface;
use Forecho\LaravelRepository\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserCriteriaCriteria implements CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param  Builder|Model  $model
     * @param  RepositoryInterface  $repository
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository): mixed
    {
        return $model->where('user_id', UserService::getUserId());
    }
}
```

Request all data without filter by request:

http://127.0.0.1:8000/api/posts

```json
[
    {
        "id": 1,
        "title": "title",
        "content": "content",
        "status": 0,
        "created_at": "2021-08-01T07:00:00.000000Z",
        "updated_at": "2021-08-01T07:00:00.000000Z"
    },
    {
        "id": 2,
        "title": "for echo",
        "content": "content",
        "status": 1,
        "created_at": "2021-08-01T07:00:00.000000Z",
        "updated_at": "2021-08-01T07:00:00.000000Z"
    }
]
```

http://127.0.0.1:8000/api/posts?title=for

```json
[
    {
        "id": 2,
        "title": "for echo",
        "content": "content",
        "status": 1,
        "created_at": "2021-08-01T07:00:00.000000Z",
        "updated_at": "2021-08-01T07:00:00.000000Z"
    }
]
```

http://127.0.0.1:8000/api/posts?status=0

```json
[
    {
        "id": 1,
        "title": "title",
        "content": "content",
        "status": 0,
        "created_at": "2021-08-01T07:00:00.000000Z",
        "updated_at": "2021-08-01T07:00:00.000000Z"
    }
]
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email caizhenghai@gmail.com instead of using the issue tracker.

## Credits

- [forecho](https://github.com/forecho)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
