<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\Request;

use FastRoute;
use HTTPRequest;
use Mockery;
use org\bovigo\vfs\vfsStream;
use PluginManager;
use Tuleap\BrowserDetection\DetectedBrowser;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\ErrorRendering;
use Tuleap\Theme\BurningParrot\BurningParrotTheme;
use function PHPUnit\Framework\assertInstanceOf;

final class FrontRouterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;

    /**
     * @var FrontRouter
     */
    private $router;
    private $url_verification_factory;
    private $route_collector;
    private $request;
    private $layout;
    private $logger;
    private $error_rendering;
    private $burning_parrot;
    private $plugin_manager;
    /**
     * @var Mockery\MockInterface|RequestInstrumentation
     */
    private $request_instrumentation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route_collector          = Mockery::mock(RouteCollector::class);
        $this->url_verification_factory = Mockery::mock(\URLVerificationFactory::class);
        $this->request                  = Mockery::mock(\HTTPRequest::class);
        $this->request->shouldReceive('getFromServer')->andReturn('Some user-agent string');
        $this->layout                  = Mockery::mock(BaseLayout::class);
        $this->logger                  = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->error_rendering         = Mockery::mock(ErrorRendering::class);
        $theme_manager                 = Mockery::mock(\ThemeManager::class);
        $this->burning_parrot          = Mockery::mock(BurningParrotTheme::class);
        $this->plugin_manager          = Mockery::mock(PluginManager::class);
        $this->request_instrumentation = Mockery::mock(RequestInstrumentation::class);

        $this->user = Mockery::mock(\PFUser::class);
        $this->request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $theme_manager->shouldReceive('getBurningParrot')->andReturn($this->burning_parrot);
        $theme_manager->shouldReceive('getTheme')->andReturn($this->layout);

        \ForgeConfig::store();
        \ForgeConfig::set('codendi_cache_dir', vfsStream::setup()->url());

        $this->router = new FrontRouter(
            $this->route_collector,
            $this->url_verification_factory,
            $this->logger,
            $this->error_rendering,
            $theme_manager,
            $this->plugin_manager,
            $this->request_instrumentation
        );
    }

    public function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['HTTP_ACCEPT']);
        unset($GLOBALS['HTML']);
        unset($GLOBALS['Response']);
        \ForgeConfig::restore();
        parent::tearDown();
    }

    public function testRouteNotFound(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->route_collector->shouldReceive('collect');
        $this->error_rendering->shouldReceive('rendersError')->once()->with(Mockery::any(), Mockery::any(), 404, Mockery::any(), Mockery::any());
        $this->request_instrumentation->shouldReceive('increment')->with(404, Mockery::type(DetectedBrowser::class))->once();

        $this->user->shouldReceive('isAnonymous')->andReturnFalse();
        $this->request->shouldReceive('isAjax')->andReturnFalse();

        $this->router->route($this->request);
    }

    public function testRouteNotFoundAnonymousUserIsNotRedirectedWhenHeaderIsNotProvided(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->request->shouldReceive('getFromServer')->andReturnFalse();
        $this->request->shouldReceive('isAjax')->andReturnFalse();

        $this->route_collector->shouldReceive('collect');
        $this->error_rendering->shouldReceive('rendersError')->once()->with(
            Mockery::any(),
            Mockery::any(),
            404,
            Mockery::any(),
            Mockery::any()
        );
        $this->request_instrumentation->shouldReceive('increment')->with(404, Mockery::type(DetectedBrowser::class))->once();

        $this->user->shouldReceive('isAnonymous')->andReturnTrue();

        $this->router->route($this->request);
    }

    public function testRouteNotFoundAnonymousUserIsNotRedirectedWhenRequestIsAjax(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->request->shouldReceive('isAjax')->andReturnTrue();

        $this->route_collector->shouldReceive('collect');
        $this->error_rendering->shouldReceive('rendersError')->once()->with(
            Mockery::any(),
            Mockery::any(),
            404,
            Mockery::any(),
            Mockery::any()
        );
        $this->request_instrumentation->shouldReceive('increment')->with(404, Mockery::type(DetectedBrowser::class))->once();

        $this->router->route($this->request);
    }

    public function testItDispatchRequestWithoutAuthz(): void
    {
        $handler = \Mockery::mock(DispatchableWithRequestNoAuthz::class);

        $handler->shouldReceive('process')->once();
        $this->request_instrumentation->shouldReceive('increment')->once();

        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn(Mockery::mock(\URLVerification::class));

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
    }

    public function testItChecksWithURLVerificationWhenDispatchingWithRequest(): void
    {
        $handler = \Mockery::mock(DispatchableWithRequest::class);

        $handler->shouldReceive('process')->with($this->request, $this->layout, [])->once();
        $this->request_instrumentation->shouldReceive('increment')->once();

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl')->with(Mockery::any(), $this->request, null)->once();
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
    }

    public function testItRaisesAnErrorWhenHandlerIsUnknown(): void
    {
        $handler = \Mockery::mock(DispatchableWithRequest::class);

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl')->with(Mockery::any(), $this->request, null)->once();
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);
        $this->request_instrumentation->shouldReceive('increment')->once();

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->logger->shouldReceive('error')->once();
        $this->error_rendering->shouldReceive('rendersErrorWithException')->once()->with(
            Mockery::any(),
            Mockery::any(),
            500,
            Mockery::any(),
            Mockery::any(),
            Mockery::any()
        );

        $this->router->route($this->request);
    }

    public function testItDispatchWithProject(): void
    {
        $handler = \Mockery::mock(DispatchableWithRequest::class . ', ' . DispatchableWithProject::class);
        $handler->shouldReceive('process')->with($this->request, $this->layout, [])->once();
        $this->request_instrumentation->shouldReceive('increment')->once();

        $project = \Mockery::mock(\Project::class);
        $handler->shouldReceive('getProject')->andReturn($project);

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl')->with(Mockery::any(), $this->request, $project)->once();
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
    }

    public function testItProvidesABurningParrotThemeWhenControllerAskForIt(): void
    {
        $handler = \Mockery::mock(DispatchableWithRequest::class . ', ' . DispatchableWithBurningParrot::class);

        $handler->shouldReceive('process')->with($this->request, $this->burning_parrot, [])->once();
        $this->request_instrumentation->shouldReceive('increment')->once();

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl');
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
    }

    public function testItProvidesABurningParrotThemeWhenControllerSelectItExplicitly(): void
    {
        $handler = new class implements DispatchableWithRequest, DispatchableWithThemeSelection {
            public bool $has_processed = false;
            public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
            {
                $this->has_processed = true;
                assertInstanceOf(BurningParrotTheme::class, $layout);
            }

            public function isInABurningParrotPage(HTTPRequest $request, array $variables): bool
            {
                return true;
            }
        };

        $this->request_instrumentation->shouldReceive('increment')->once();

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl');
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);

        self::assertTrue($handler->has_processed);
    }

    public function testItInstantiatePluginsWhenRoutingAPluginRoute(): void
    {
        $controller = Mockery::mock(DispatchableWithRequest::class);
        $controller->shouldReceive('process')->once();
        $this->request_instrumentation->shouldReceive('increment')->once();

        $this->plugin_manager->shouldReceive('getPluginByName')->with('foobar')->andReturns(
            new class ($controller) {
                private $controller;

                public function __construct($controller)
                {
                    $this->controller = $controller;
                }

                public function myHandler()
                {
                    return $this->controller;
                }
            }
        );

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl');
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) {
            $r->get('/stuff', ['plugin' => 'foobar', 'handler' => 'myHandler']);

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
    }

    public function testItRoutesToRouteCollectorWithParams(): void
    {
        $this->request_instrumentation->shouldReceive('increment')->once();
        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl');
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('myHandler')->with('some_param1', 'some_param2')->andReturns(Mockery::spy(DispatchableWithRequest::class));

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) {
            $r->get('/stuff', ['core' => true, 'handler' => 'myHandler', 'params' => ['some_param1', 'some_param2']]);

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
    }

    /**
     * @testWith [200]
     *           [500]
     *           [302]
     *           [419]
     *           [101]
     * @runInSeparateProcess
     */
    public function testHTTPStatusCodeIsCorrectlyRecorded(int $status_code): void
    {
        $handler = \Mockery::mock(DispatchableWithRequestNoAuthz::class);
        $handler->shouldReceive('process');

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler, $status_code) {
            $r->get('/stuff', function () use ($handler, $status_code) {
                http_response_code($status_code);

                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->request_instrumentation->shouldReceive('increment')->with($status_code, Mockery::type(DetectedBrowser::class))->once();

        $this->router->route($this->request);
    }

    public function testHttpStatusCodeIsEqualToExceptionCodeIfTheExceptionImplementsCodeIsAValidHTTPStatus(): void
    {
        $exception = new class ("Conflict", 409) extends \Exception implements CodeIsAValidHTTPStatus {
        };

        $handler = \Mockery::mock(DispatchableWithRequestNoAuthz::class);
        $handler->shouldReceive('process')->andThrow($exception);

        $this->route_collector->shouldReceive('collect')->with(
            Mockery::on(
                function (FastRoute\RouteCollector $r) use ($handler) {
                    $r->get(
                        '/stuff',
                        function () use ($handler) {
                            return $handler;
                        }
                    );

                    return true;
                }
            )
        );

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->request_instrumentation->shouldReceive('increment')->with(
            409,
            Mockery::type(DetectedBrowser::class)
        )->once();

        $this->logger
            ->shouldReceive('error')
            ->with('Caught exception', ['exception' => $exception])
            ->once();

        $this->error_rendering
            ->shouldReceive('rendersErrorWithException')
            ->with(
                Mockery::any(),
                Mockery::any(),
                409,
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
            )->once();

        $this->router->route($this->request);
    }

    public function testHttpStatusCodeIs500IfTheExceptionDoesNotImplementCodeIsAValidHTTPStatus(): void
    {
        $exception = new class ("Conflict", 409) extends \Exception {
        };

        $handler = \Mockery::mock(DispatchableWithRequestNoAuthz::class);
        $handler->shouldReceive('process')->andThrow($exception);

        $this->route_collector->shouldReceive('collect')->with(
            Mockery::on(
                function (FastRoute\RouteCollector $r) use ($handler) {
                    $r->get(
                        '/stuff',
                        function () use ($handler) {
                            return $handler;
                        }
                    );

                    return true;
                }
            )
        );

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->request_instrumentation->shouldReceive('increment')->with(
            500,
            Mockery::type(DetectedBrowser::class)
        )->once();

        $this->logger
            ->shouldReceive('error')
            ->with('Caught exception', ['exception' => $exception])
            ->once();

        $this->error_rendering
            ->shouldReceive('rendersErrorWithException')
            ->with(
                Mockery::any(),
                Mockery::any(),
                500,
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
            )->once();

        $this->router->route($this->request);
    }
}
