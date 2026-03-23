<?php

namespace Modules\Order\Providers;

use App\Services\MenuService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Order\Console\Commands\CartCreateCommand;
use Modules\Order\Console\Commands\CartListCommand;
use Modules\Order\Console\Commands\CartStatsCommand;
use Modules\Order\Console\Commands\OrderCreateCommand;
use Modules\Order\Console\Commands\OrderListCommand;
use Modules\Order\Console\Commands\OrderPushCommand;
use Modules\Order\Console\Commands\OrderSimulateCommand;
use Modules\Order\Console\Commands\OrderStatsCommand;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class OrderServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Order';

    protected string $nameLower = 'order';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerMenuItems();
    }

    /**
     * Register menu items for the Order module.
     */
    protected function registerMenuItems(): void
    {
        $this->app->booted(function () {
            // Add main menu item
            MenuService::addMenuItem(
                'primary',
                'order',
                __('Orders'),
                route('order.orders.index'),
                'ShoppingBag',
                60,
                'orders.view_any',
                'order.*'
            );

            // All Orders submenu
            MenuService::addSubmenuItem(
                'primary',
                'order',
                __('All Orders'),
                route('order.orders.index'),
                1,
                'orders.view_any',
                'order.orders.*',
                'Package'
            );

            // Carts submenu
            MenuService::addSubmenuItem(
                'primary',
                'order',
                __('Carts'),
                route('order.carts.index'),
                2,
                'carts.view_any',
                'order.carts.*',
                'ShoppingCart'
            );

            // Product Reviews submenu
            MenuService::addSubmenuItem(
                'primary',
                'order',
                __('Product Reviews'),
                route('order.product-reviews.index'),
                3,
                'product_reviews.view_any',
                'order.product-reviews.*',
                'Star'
            );

            // Outlet Reviews submenu
            MenuService::addSubmenuItem(
                'primary',
                'order',
                __('Outlet Reviews'),
                route('order.outlet-reviews.index'),
                4,
                'outlet_reviews.view_any',
                'order.outlet-reviews.*',
                'Store'
            );
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            OrderCreateCommand::class,
            OrderListCommand::class,
            OrderPushCommand::class,
            OrderSimulateCommand::class,
            OrderStatsCommand::class,
            CartCreateCommand::class,
            CartListCommand::class,
            CartStatsCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
