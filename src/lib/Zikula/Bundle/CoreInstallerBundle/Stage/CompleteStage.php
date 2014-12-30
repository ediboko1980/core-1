<?php
/**
 * Copyright Zikula Foundation 2014 - Zikula CoreInstaller bundle.
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Zikula
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\Bundle\CoreInstallerBundle\Stage;

use Zikula\Component\Wizard\InjectContainerInterface;
use Zikula\Component\Wizard\StageInterface;
use Zikula\Component\Wizard\WizardCompleteInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Zikula\Bundle\CoreBundle\YamlDumper;
use Zikula\Core\Event\ModuleStateEvent;
use \Zikula\Core\CoreEvents;

class CompleteStage implements StageInterface, WizardCompleteInterface, InjectContainerInterface
{
    private $container;
    private $yamlManager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->finishInstall();
        $this->yamlManager = new YamlDumper($this->container->get('kernel')->getRootDir() .'/config', 'dynamic/installerTemp.yml');
    }

    public function getName()
    {
        return 'complete';
    }

    public function getTemplateName()
    {
        return '';
    }

    public function isNecessary()
    {
        return true;
    }

    public function getTemplateParams()
    {
        return array();
    }

    /**
     * @return string
     */
    public function getResponse(Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', 'Congratulations! Zikula has been successfully installed.');

        return new RedirectResponse($this->container->get('router')->generate('zikulaadminmodule_admin_adminpanel', array(), true));
    }

    private function finishInstall()
    {
        $this->container->get('session')->start();
        $params = $this->yamlManager->getParameters();

        // login as admin using provided credentials
        $authenticationInfo = array(
            'login_id'  => $params['username'],
            'pass'      => $params['password']
        );
        $authenticationMethod = array(
            'modname'   => 'ZikulaUsersModule',
            'method'    => 'uname',
        );
        \UserUtil::loginUsing($authenticationMethod, $authenticationInfo);

        // Set the System Identifier as a unique string.
        \System::setVar('system_identifier', str_replace('.', '', uniqid(rand(1000000000, 9999999999), true)));

        // add admin email as site email
        \System::setVar('adminmail', $params['email']);

        // regenerate the theme list
        \Zikula\Module\ThemeModule\Util::regenerate();

        // add remaining parameters and remove unneeded ones
        $params = $this->yamlManager->getParameters();
        unset($params['username'], $params['password'], $params['email']);
        $params['datadir'] = 'userdir';
        $params['installed'] = true;
        $this->yamlManager->setParameters($params);

        // write legacy config file
        $this->container->get('core_installer.config.util')->writeLegacyConfig();

        // protect config.php file
        foreach (array(
                     $this->container->get('kernel')->getRootDir().'config/config.php',
                     $this->container->get('kernel')->getRootDir().'app/config/parameters.yml'
                 ) as $file) {
            @chmod($file, 0400);
            if (!is_readable($file)) {
                @chmod($file, 0440);
                if (!is_readable($file)) {
                    @chmod($file, 0444);
                }
            }
        }

        // install all plugins
        $systemPlugins = \PluginUtil::loadAllSystemPlugins();
        foreach ($systemPlugins as $plugin) {
            \PluginUtil::install($plugin);
        }

        // fire MODULE_INSTALL event to reload all routes
        $event = new ModuleStateEvent($this->container->get('kernel')->getModule('ZikulaRoutesModule'));
        $this->container->get('event_dispatcher')->dispatch(CoreEvents::MODULE_POSTINSTALL, $event);

        \System::setInstalling(false);
    }
}