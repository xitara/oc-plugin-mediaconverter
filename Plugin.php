<?php namespace Xitara\MediaConverter;

use Backend;
use System\Classes\PluginBase;
use Xitara\MediaConverter\Classes\Convert;

/**
 * MediaConverter Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'MediaConverter',
            'description' => 'No description provided yet...',
            'author' => 'Xitara',
            'icon' => 'icon-leaf',
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('mediaconverter.convert', 'Xitara\MediaConverter\Console\Convert');
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        // Log::debug(__METHOD__);
        // Convert::delete();
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Xitara\MediaConverter\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'xitara.mediaconverter.some_permission' => [
                'tab' => 'MediaConverter',
                'label' => 'Some permission',
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'mediaconverter' => [
                'label' => 'MediaConverter',
                'url' => Backend::url('xitara/mediaconverter/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['xitara.mediaconverter.*'],
                'order' => 500,
            ],
        ];
    }

    public function registerSchedule($schedule)
    {
        $schedule->call(function () {
            $convert = new Convert;
            $convert->convert();
        })
            ->name('MediaConverter')
            ->withoutOverlapping()
            ->cron('* * * * *');

        // $schedule
        // ->command('mediaconverter:convert')
        // ->withoutOverlapping()
        // ->cron('* * * * *');
    }

}
