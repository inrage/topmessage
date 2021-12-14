<?php
/**
 * 2007-2021 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Topmessage extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'topmessage';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'inRage Team';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Top Message');
        $this->description = $this->l('Displays a message before the header of the page');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('TOPMESSAGE_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBanner');
    }

    public function uninstall()
    {
        Configuration::deleteByName('TOPMESSAGE_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitTopmessageModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitTopmessageModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'TOPMESSAGE_LIVE_MODE',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 5,
                        'type' => 'text',
                        'required' => true,
                        'name' => 'TOPMESSAGE_MESSAGE',
                        'label' => $this->l('Message'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'TOPMESSAGE_LINK',
                        'label' => $this->l('Link'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'date',
                        'name' => 'TOPMESSAGE_DATE_IN',
                        'label' => $this->l('Start At'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'date',
                        'name' => 'TOPMESSAGE_DATE_OUT',
                        'label' => $this->l('End At'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'TOPMESSAGE_LIVE_MODE' => Configuration::get('TOPMESSAGE_LIVE_MODE', true),
            'TOPMESSAGE_MESSAGE' => Configuration::get('TOPMESSAGE_MESSAGE', 'Welcome to our shop'),
            'TOPMESSAGE_LINK' => Configuration::get('TOPMESSAGE_LINK', null),
            'TOPMESSAGE_DATE_IN' => Configuration::get('TOPMESSAGE_DATE_IN', null),
            'TOPMESSAGE_DATE_OUT' => Configuration::get('TOPMESSAGE_DATE_OUT', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookDisplayBanner()
    {
        $dateValid = true;
        $now = (new DateTime())->format('Y-m-d');

        if ($now < Configuration::get('TOPMESSAGE_DATE_IN')) {
            $dateValid = false;
        }

        if ($now > Configuration::get('TOPMESSAGE_DATE_OUT')) {
            $dateValid = false;
        }

        if (!Configuration::get('TOPMESSAGE_LIVE_MODE') || !$dateValid) {
            return '';
        }


        $this->context->smarty->assign([
            'message' => Configuration::get('TOPMESSAGE_MESSAGE'),
            'message_link' => Configuration::get('TOPMESSAGE_LINK'),
        ]);

        return $this->display(__FILE__, '/views/templates/hook/displayBanner.tpl');
    }
}
