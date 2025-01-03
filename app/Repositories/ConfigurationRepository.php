<?php
namespace App\Repositories;

use App\Models\Configuration;
class ConfigurationRepository
{
    protected $config;

    /**
     * Instantiate a new instance.
     *
     * @return void
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Get all config variables
     * @return Configuration
     */
    public function getAll()
    {
        return $this->config->get()->pluck('value', 'name');
    }

    /**
     * Get all config variables by public value
     * @return Configuration
     */
    public function getAllPublic()
    {
        return $this->config->get()->pluck('public_value', 'name');
    }

    /**
     * Get config variable by name
     * @return Configuration
     */
    public function getByName($names)
    {
        return $this->config->filterByName($names)->get()->value;
    }

    /**
     * Get selected config variables by name
     * @return Configuration
     */
    public function getSelectedByName($names)
    {
        return $this->config->whereIn('name', $names)->get()->pluck('value', 'name')->all();
    }

    /**
     * Find configuration by name else create.
     *
     * @param array $params
     * @return null
     */
    public function firstOrCreate($name)
    {
        return $this->config->firstOrCreate(['name' => $name]);
    }

    /**
     * Store a configuration
     *
     * @param array $params
     * @return null
     */
    public function set($name, $value, $private = 0)
    {
        $config = $this->firstOrCreate([
            'name' => $name
        ]);

        if (is_array($value) || is_object($value)) {
            $config->json_value = $value;
            $config->text_value = null;
            $config->numeric_value = null;
            $config->save();
        } else {
            $config->text_value = ($value ? !is_numeric($value) || (is_numeric($value) && strlen($value) > 10 ? $value : null) : null);
            $config->numeric_value = is_numeric($value) && strlen($value) <= 10 ? $value : null;
        }
        $config->is_private = $private;
        $config->save();

        return $config;
    }

    /**
     * Store configuration.
     *
     * @param array $params
     * @return null
     */
    public function store($params)
    {
        $config_type = isset($params['config_type']) ? $params['config_type'] : null;

        $this->smsConfiguration($params);
        foreach ($params as $key => $value) {
            if (!in_array($key, ['config_type', 'providers']) && (!in_array($key, config('system.private_config_variables')) || (in_array($key, config('system.private_config_variables')) && $value != config('system.hidden_field')))) {
                // $value = $value;

                $config = $this->firstOrCreate($key);

                if (is_array($value) || is_object($value)) {
                    $config->json_value = $value;
                    $config->text_value = null;
                    $config->numeric_value = null;
                    $config->save();
                } else {
                    $config->numeric_value = is_integer($value) ? $value : null;
                    $config->text_value = is_string($value) ? $value : null;
                   // $config->numeric_value = is_numeric($value) && strlen($value) <= 10 ? $value : null;
                    $config->save();
                }
            }
        }

        $this->setLocale($params);

        $this->setVisibility();

        if ($config_type === 'mail' || $config_type === 'system' || $config_type === 'sms') {
            config(['config' => $this->getAll()]);
            $this->setEnv($config_type);
        }


    }



    /**
     * Store locale configuration.
     *
     * @param array $params
     * @return null
     */
    public function setLocale($params)
    {
        $config_type = isset($params['config_type']) ? $params['config_type'] : null;
        $locale = isset($params['locale']) ? $params['locale'] : config('app.locale');

        if ($config_type != 'system') {
            return;
        }

        if ($locale === config('app.locale')) {
            return;
        }

        config(['app.locale' => $locale]);
        \App::setLocale(config('app.locale'));
        \Cache::forget('lang.js');
    }

    /**
     * Set configuration visibility.
     *
     * @param array $params
     * @return null
     */
    public function setVisibility()
    {
        $this->config->whereIn('name', config('system.private_config_variables') ?? [])->update(['is_private' => 1]);
        $this->config->whereNotIn('name', config('system.private_config_variables') ?? [])->update(['is_private' => 0]);
    }

    /**
     * Set default configuration variable.
     *
     * @return null
     */
    public function setDefault()
    {

        $system_variables = getVar('system');
        $default_config = isset($system_variables['default_config']) ? $system_variables['default_config'] : [];

        foreach ($default_config as $key => $value) {
            $config = $this->firstOrCreate($key);
            if (is_array($value) || is_object($value)) {
                $config->json_value = $value;
                $config->text_value = null;
                $config->numeric_value = null;
                $config->save();
            } elseif (!is_numeric($config->numeric_value) && ($config->value === '' || $config->value === null)) {
                $config->numeric_value = is_numeric($value) ? $value : null;
                $config->text_value = !is_numeric($value) ? $value : null;
                $config->json_value = null;
                $config->save();
            }
        }

        config(['config' => $this->getAll()]);
        config(['system' => $system_variables]);


        $this->setVisibility();

        date_default_timezone_set(config('config.timezone') ?: 'Europe/Vilnius');
        \App::setLocale(config('config.locale') ?: 'en');
    }

    /**
     * Set .env files.
     *
     * @return null
     */
    public function setEnv($type = null)
    {
        if (!$type) {
            return;
        }

        if ($type === 'system') {
            envu(['APP_DEBUG' => (!\App::environment('production') && config('config.error_display')) ? true : false]);
        }

        if ($type === 'sms') {

            envu([
                'NEXMO_KEY' => config('config.NEXMO_KEY'),
                'NEXMO_SECRET' => config('config.NEXMO_SECRET'),
                'NEXMO_NUMBER' => config('config.NEXMO_NUMBER')
            ]);
        }

        if ($type === 'mail') {
            envu([
                'MAIL_DRIVER' => config('config.driver'),
                'MAIL_FROM_ADDRESS' => config('config.from_address'),
                'MAIL_FROM_NAME' => config('config.from_name')
            ]);

            if (config('config.driver') === 'smtp') {
                envu([
                    'MAIL_HOST' => config('config.smtp_host'),
                    'MAIL_PORT' => config('config.smtp_port'),
                    'MAIL_USERNAME' => config('config.smtp_username'),
                    'MAIL_PASSWORD' => config('config.smtp_password'),
                    'MAIL_ENCRYPTION' => config('config.smtp_encryption'),
                ]);
            } elseif (config('config.driver') === 'mailgun') {
                envu([
                    'MAIL_HOST' => config('config.mailgun_host'),
                    'MAIL_PORT' => config('config.mailgun_port'),
                    'MAIL_USERNAME' => config('config.mailgun_username'),
                    'MAIL_PASSWORD' => config('config.mailgun_password'),
                    'MAIL_ENCRYPTION' => config('config.mailgun_encryption'),
                    'MAILGUN_DOMAIN' => config('config.mailgun_domain'),
                    'MAILGUN_SECRET' => config('config.mailgun_secret'),
                ]);
            } elseif (config('config.driver') === 'mandrill') {
                envu([
                    'MANDRILL_SECRET' => config('config.mandrill_secret'),
                ]);
            }
        }
    }

}
