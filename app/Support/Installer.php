<?php

namespace Plugins\DcatSaas\Support;

class Installer
{
    protected $config = [
        // [
        //     'item_tag' => 'dcat-saas',
        //     'item_key' => 'access_key',
        //     'item_type' => 'string',
        //     'item_value' => null,
        // ],
    ];

    public function process(callable $callable)
    {
        foreach ($this->config as $configItem) {
            $callable($configItem);
        }
    }

    // plugin install
    public function install()
    {
        $this->process(function ($configItem) {
            // add config
        });
    }

    /// plugin uninstall
    public function uninstall()
    {
        $this->process(function ($configItem) {
            // remove config
        });
    }
}
