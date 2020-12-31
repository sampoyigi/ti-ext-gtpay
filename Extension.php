<?php namespace SamPoyigi\GTPay;

use Admin\Facades\AdminAuth;
use Illuminate\Foundation\AliasLoader;
use System\Classes\BaseExtension;

/**
 * GTPay Extension Information File
 */
class Extension extends BaseExtension
{
    public function extensionMeta()
    {
        return [
            'name' => 'GTPay for TastyIgniter',
            'author' => 'SamPoyigi',
            'description' => 'Provides support for payment processing using GTPay.',
            'icon' => 'fa-plug',
            'version' => '1.0.0',
        ];
    }

    public function register()
    {
    }

    public function registerPaymentGateways()
    {
        return [
            'SamPoyigi\GTPay\Payments\GTPay' => [
                'code' => 'gtpay',
                'name' => 'GTPay Payment',
                'description' => 'Accept credit card payments using GTPay',
            ],
        ];
    }

    /**
     * Registers any back-end permissions used by this extension.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'SamPoyigi.GTPay.Manage' => [
                'group' => 'module',
                'description' => 'Ability to the manage GTPay payment gateway settings',
            ],
        ];
    }
}
