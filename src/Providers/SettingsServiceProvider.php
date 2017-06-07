<?php

namespace CubeSystems\Leaf\Providers;

use Cartalyst\Support\ServiceProvider;
use CubeSystems\Leaf\Admin\Settings\Setting;
use CubeSystems\Leaf\Admin\Settings\SettingDefinition;
use CubeSystems\Leaf\Admin\Settings\Settings;
use CubeSystems\Leaf\Services\SettingRegistry;
use Illuminate\Foundation\Application;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * @var SettingRegistry
     */
    protected $settingRegistry;

    /**
     * @param Application $app
     * @param SettingRegistry $settingRegistry
     */
    public function __construct(
        Application $app,
        SettingRegistry $settingRegistry = null
    )
    {
        parent::__construct( $app );

        $this->settingRegistry = $settingRegistry;
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton( SettingRegistry::class, function()
        {
            return new SettingRegistry();
        } );

        $this->app->singleton( 'leaf_settings', function()
        {
            return new Settings( $this->app[ SettingRegistry::class ] );
        } );

        $this->settingRegistry = $this->app[ SettingRegistry::class ];

        $this->importFromConfig( include config_path( 'settings.php' ) );
    }

    /**
     * @return void
     */
    public function importFromDatabase()
    {
        Setting::all()->each( function( Setting $setting )
        {
            $definition = new SettingDefinition( $setting->name, $setting->value, $setting->type );
            $this->settingRegistry->register( $definition );

            $this->app[ 'config' ]->set( 'settings.' . $setting->name, $setting->value );
        } );
    }

    /**
     * @param array $properties
     * @param string $before
     */
    public function importFromConfig( array $properties, $before = '' )
    {
        foreach( $properties as $key => $data )
        {
            if( is_array( $data ) && !empty( $data ) && !array_key_exists( 'value', $data ) )
            {
                $this->importFromConfig( $data, $before . $key . '.' );
            }
            else
            {
                $key = $before . $key;
                $value = $data[ 'value' ] ?? $data;
                $type = $data[ 'type' ] ?? null;

                $definition = new SettingDefinition( $key, $value, $type );
                $this->settingRegistry->register( $definition );
            }
        }
    }
}