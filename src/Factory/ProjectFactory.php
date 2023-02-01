<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Project>
 *
 * @method static Project|Proxy                     createOne(array $attributes = [])
 * @method static Project[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Project|Proxy                     find(object|array|mixed $criteria)
 * @method static Project|Proxy                     findOrCreate(array $attributes)
 * @method static Project|Proxy                     first(string $sortedField = 'id')
 * @method static Project|Proxy                     last(string $sortedField = 'id')
 * @method static Project|Proxy                     random(array $attributes = [])
 * @method static Project|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Project[]|Proxy[]                 all()
 * @method static Project[]|Proxy[]                 findBy(array $attributes)
 * @method static Project[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Project[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static ProjectRepository|RepositoryProxy repository()
 * @method        Project|Proxy                     create(array|callable $attributes = [])
 */
final class ProjectFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->sentence(random_int(1, 3)),
        ];
    }

    protected static function getClass(): string
    {
        return Project::class;
    }

    private function clean(string $string): string
    {
        return preg_replace('/[^A-Za-z0-9\- ]/', '', $string); // Removes special chars.
    }
}
