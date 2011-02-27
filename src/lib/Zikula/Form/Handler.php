<?php
/**
 * Copyright Zikula Foundation 2009 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Zikula_Form
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

/**
 * Base form handler class
 *
 * This is the base class to inherit from when creating your own form handlers.
 *
 * Member variables in a form handler object is persisted accross different page requests. This means
 * a member variable $this->x can be set on one request and on the next request it will still contain
 * the same value.
 *
 * A form handler will be notified of various events that happens during it's life-cycle.
 * When a specific event occurs then the corresponding event handler (class method) will be executed. Handlers
 * are named exactly like their events - this is how the framework knows which methods to call.
 *
 * The list of events is:
 *
 * - <b>initialize</b>: this event fires before any of the events for the plugins and can be used to setup
 *   the form handler. The event handler typically takes care of reading URL variables, access control
 *   and reading of data from the database.
 *
 * - <b>handleCommand</b>: this event is fired by various plugins on the page. Typically it is done by the
 *   Zikula_Form_Plugin_Button plugin to signal that the user activated a button.
 */
abstract class Zikula_Form_Handler implements Zikula_Translatable
{
    /**
     * Translation domain.
     *
     * @var string
     */
    protected $domain;

    /**
     * View instance.
     *
     * @var Zikula_Form_View
     */
    protected $view;

    /**
     * This name.
     *
     * @var string
     */
    protected $name;

    /**
     * Post construction hook.
     *
     * @return mixed
     */
    public function setup()
    {
    }

    /**
     * Getter for view.
     *
     * @return Zikula_Form_View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Setter for view property.
     * 
     * @param Zikula_Form_View $view Zikula_Form_View.
     *
     * @return void
     */
    public function setView(Zikula_Form_View $view)
    {
        $this->view = $view;
    }

    /**
     * Get translation domain.
     *
     * @return string $this->domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set domain property.
     * 
     * @param string $domain Domain.
     *
     * @return void
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Get name property.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name property.
     *
     * @param string $name Name.
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * Initialize form handler.
     *
     * Typical use:
     * <code>
     * function initialize($view)
     * {
     *     if (!HasAccess) { // your access check here
     *        return $view->setErrorMsg('No access');
     *     }
     *
     *     $id = $this->request->getPost()->get('id');
     *     $data = ModUtil::apiFunc('MyModule', 'user', 'get', array('id' => $id));
     *     if (count($data) == 0) {
     *         return $view->setErrorMsg('Unknown data');
     *     }
     *
     *     $view->assign($data);
     *     return true;
     * }
     * </code>
     *
     * @param Zikula_Form_View $view Reference to Form render object.
     *
     * @return bool False in case of initialization errors, otherwise true. If false is returned then the
     * framework assumes that {@link Zikula_Form_View::setErrorMsg()} has been called with a suitable
     * error message.
     */
    public function initialize(Zikula_Form_View $view)
    {
        return true;
    }

    /**
     * Pre-initialise hook.
     *
     * @return void
     */
    public function preInitialize()
    {
    }

    /**
     * Post-initialise hook.
     *
     * @return void
     */
    public function postInitialize()
    {
    }

    /**
     * Command event handler.
     *
     * This event handler is called when a command is issued by the user. Commands are typically something
     * that originates from a {@link Zikula_Form_Plugin_Button} plugin. The passed args contains different properties
     * depending on the command source, but you should at least find a <var>$args['commandName']</var>
     * value indicating the name of the command. The command name is normally specified by the plugin
     * that initiated the command.
     *
     * @param Zikula_Form_View $view  Reference to Form render object.
     * @param array            &$args Arguments of the command.
     *
     * @see    Zikula_Form_Plugin_Button, Zikula_Form_Plugin_ImageButton
     * @return void
     */
    public function handleCommand(Zikula_Form_View $view, &$args)
    {
    }

    /**
     * Notify any hookable events.
     *
     * @param string $name    The event name for the hookable event.
     * @param mixed  $subject The subject of the event.
     * @param mixed  $id      The ID of the subject.
     * @param array  $args    Extra meta data.
     * @param mixes  $data    Any data to filter.
     *
     * @return Zikula_Event
     */
    public function notifyHooks($name, $subject=null, $id=null, $args=array(), $data=null)
    {
        // set ID.
        $args['id'] = $id;

        if (!isset($args['controller'])) {
            $args['controller'] = $this->view->get_tpl_var('controller');
        }

        if (!$args['controller'] instanceof Zikula_Controller) {
            throw new InvalidArgumentException(__f('%s is not an instance of Zikula_Controller, the $args[\'controller\'] argument must be the controller who is notifying these hooks', get_class($this)));
        }

        // set caller's name
        $args['caller'] = $args['controller']->getName();

        $event = new Zikula_Event($name, $subject, $args, $data);
        return $this->view->getEventManager()->notify($event);
    }

    /**
     * Convenience Module SetVar.
     *
     * @param string $key   Key.
     * @param mixed  $value Value, default empty.
     *
     * @return object This.
     */
    public function setVar($key, $value='')
    {
        ModUtil::setVar($this->name, $key, $value);
        return $this;
    }

    /**
     * Convenience Module SetVars.
     *
     * @param array $vars Array of key => value.
     *
     * @return object This.
     */
    public function setVars(array $vars)
    {
        ModUtil::setVars($this->name, $vars);
        return $this;
    }

    /**
     * Convenience Module GetVar.
     *
     * @param string  $key     Key.
     * @param boolean $default Default, false if not found.
     *
     * @return mixed
     */
    public function getVar($key, $default=false)
    {
        return ModUtil::getVar($this->name, $key, $default);
    }

    /**
     * Convenience Module GetVars for all keys in this module.
     *
     * @return mixed
     */
    public function getVars()
    {
        return ModUtil::getVar($this->name);
    }

    /**
     * Convenience Module DelVar.
     *
     * @param string $key Key.
     *
     * @return object This.
     */
    public function delVar($key)
    {
        ModUtil::delVar($this->name, $key);
        return $this;
    }

    /**
     * Convenience Module DelVar for all keys for this module.
     *
     * @return object This.
     */
    public function delVars()
    {
        ModUtil::delVar($this->name);
        return $this;
    }

    /**
     * Translate.
     *
     * @param string $msgid String to be translated.
     *
     * @return string
     */
    public function __($msgid)
    {
        return __($msgid, $this->domain);
    }

    /**
     * Translate with sprintf().
     *
     * @param string       $msgid  String to be translated.
     * @param string|array $params Args for sprintf().
     *
     * @return string
     */
    public function __f($msgid, $params)
    {
        return __f($msgid, $params, $this->domain);
    }

    /**
     * Translate plural string.
     *
     * @param string $singular Singular instance.
     * @param string $plural   Plural instance.
     * @param string $count    Object count.
     *
     * @return string Translated string.
     */
    public function _n($singular, $plural, $count)
    {
        return _n($singular, $plural, $count, $this->domain);
    }

    /**
     * Translate plural string with sprintf().
     *
     * @param string       $sin    Singular instance.
     * @param string       $plu    Plural instance.
     * @param string       $n      Object count.
     * @param string|array $params Sprintf() arguments.
     *
     * @return string
     */
    public function _fn($sin, $plu, $n, $params)
    {
        return _fn($sin, $plu, $n, $params, $this->domain);
    }
}
