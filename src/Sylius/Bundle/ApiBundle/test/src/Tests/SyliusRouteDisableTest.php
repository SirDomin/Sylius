<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\ApiBundle\Application\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SyliusRouteDisableTest extends ApiTestCase
{
    use SetUpTestsTrait;

    public function setUp(): void
    {
        $this->setFixturesFiles([]);

        $this->setUpTest();
    }

    /**
     * @test
     */
    public function it_removes_api_method_to_endpoint(): void
    {
        static::createClient()->request(
            'GET',
            '/api/v2/admin/zones',
            ['auth_bearer' => $this->JWTAdminUserToken]
        );

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['hydra:description' => 'No route found for "GET http://example.com/api/v2/admin/zones"']);
    }
}
