<?php
/**
 * toKernel - Universal PHP Framework.
 * Parent module class for modules. All module classes
 * must to be inherited this, for correct functionality.
 *
 * This file is part of toKernel.
 *
 * toKernel is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * toKernel is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with toKernel. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   kernel
 * @package    framework
 * @subpackage kernel
 * @author     toKernel development team <framework@tokernel.com>
 * @copyright  Copyright (c) 2018 toKernel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @version    2.3.5
 * @link       http://www.tokernel.com
 * @since      File available since Release 1.0.0
 * @todo       Remove deprecated functionality
 */

/* Restrict direct access to this file */
defined('TK_EXEC') or die('Restricted area.');

/**
 * module class
 *
 * @author David Ayvazyan <tokernel@gmail.com>
 */
class module {

    /**
     * Status of module
     *
     * @access protected
     * @staticvar bool
     */
    protected static $initialized;

    /**
     * Library object for working with
     * libraries in this class
     *
     * @var object
     * @access protected
     */
    protected $lib;

    /**
     * Application object for accessing
     * aplication functions in this class
     *
     * @var object
     * @access protected
     */
    protected $app;

    /**
     * This module id
     *
     * @access protected
     * @var string
     */
    protected $id;

    /**
     * Addon id
     *
     * @access protected
     * @var string
     */
    protected $id_addon;

    /**
     * Addon configuration object
     *
     * @access protected
     * @var object
     */
    protected $config;

    /**
     * Addon log instance
     *
     * @var object
     * @access protected
     */
    protected $log;

    /**
     * Addon language
     *
     * @access protected
     * @var object
     */
    protected $language;

    /**
     * Parameters
     *
     * @access protected
     * @var array
     */
    protected $params;

    /**
     * Class Constructor
     *
     * @param mixed $params = NULL
     * @param string $id_addon
     * @param object $config
     * @param object $log
     * @param object $language
     */
    public function __construct($params = NULL, $id_addon, ini_lib $config, log_lib $log, language_lib $language) {

        // Define main objects
        $this->lib = lib::instance();
        $this->app = app::instance();

        // Define Addon ID
        $this->id_addon = $id_addon;

        // Define Addon configuration object
        $this->config = $config;

        // Define Addon log object
        $this->log = $log;

        $this->params = $params;

        /* Define module id */
        $tmp_id = get_class($this);

        // Define module id related to stage, if extended
        /* @deprecated */
        if (substr($tmp_id, -11) == '_ext_module') {
            $this->id = substr($tmp_id, 0, -11);
        } else {
            $this->id = substr($tmp_id, 0, -7);
        }

        // Initialize language
        $this->language = $this->lib->language->instance(
            $this->app->language(),
            array(
                TK_CUSTOM_PATH . 'addons' . TK_DS . $this->id_addon .
                TK_DS . 'languages' . TK_DS,

                TK_PATH . 'addons' . TK_DS . $this->id_addon .
                TK_DS . 'languages' . TK_DS,

                TK_CUSTOM_PATH . 'addons' . TK_DS . $this->id_addon .
                TK_DS . 'modules' . TK_DS . $this->id .
                TK_DS . 'languages' . TK_DS,

                TK_PATH . 'addons' . TK_DS . $this->id_addon .
                TK_DS . 'modules' . TK_DS . $this->id .
                TK_DS . 'languages' . TK_DS,
            ),
            'Addon: ' . $this->id_addon . ' Module: ' . $this->id,
            true);

        self::$initialized = true;

    } // End class constructor

    /**
     * Load module by parent addon object
     *
     * @final
     * @access public
     * @param string $id_module
     * @param array $params
     * @param bool $clone
     * @return object
     */
    final public function load_module($id_module, $params = array(), $clone = false) {
        $parent_addon = $this->id_addon;
        return $this->lib->addons->$parent_addon->load_module($id_module, $params, $clone);
    }

    /**
     * Load view file for module and return `view` object.
     * Include view file from application dir if exists,
     * else include from framework dir.
     * Return false, if view file not exists.
     *
     * @final
     * @access public
     * @param string $file
     * @param array $params = array()
     * @return mixed
     * @since 2.1.0
     */
    final public function load_view($file, $params = array()) {

        $view_dir = $this->id;

        /* Remove addon name from class name */
        $view_dir = substr($view_dir, (strlen($this->id_addon) + 1), 100);

        /* Parent view class included in tokernel.inc.php */
        $app_view_file = TK_CUSTOM_PATH . 'addons' . TK_DS . $this->id_addon .
            TK_DS . 'modules' . TK_DS . $view_dir . TK_DS .
            'views' . TK_DS . $file . '.view.php';

        /* @deprecated */
        $tk_view_file = TK_PATH . 'addons' . TK_DS . $this->id_addon .
            TK_DS . 'modules' . TK_DS . $view_dir . TK_DS .
            'views' . TK_DS . $file . '.view.php';

        /*
         * Define existing file for include.
         * return false if view file not exists.
         */
        if (is_file($app_view_file)) {
            $file_to_load = $app_view_file;
            $loaded_from_custom = true;
        } elseif (is_file($tk_view_file)) {
            /* @deprecated */
            $file_to_load = $tk_view_file;
            $loaded_from_custom = false;
        } else {

            $loaded_from_custom = false;

            tk_e::log_debug('There are no view file "' . $file . '" to load.',
                get_class($this) . '->' . __FUNCTION__);

            trigger_error('There are no view file `' . $file . '.view.php` ' .
                'for module `' . $this->id . '`. Addon: ' .
                $this->id_addon, E_USER_ERROR);

            return false;
        }

        if (!$loaded_from_custom) {
            /* @deprecated */
            tk_e::log_debug($file . ' from framework path with params: Array[' .
                count($params) . ']', get_class($this) . '->' . __FUNCTION__);
        } else {
            tk_e::log_debug($file . ' from application path with params: Array[' .
                count($params) . ']', get_class($this) . '->' . __FUNCTION__);
        }

        /* Return view object */
        /* @deprecated Remove $this->config, $this->log from View */
        return new view($file_to_load, $this->id, $this->config, $this->log, $this->language, $params);

    } // end func load_view

    /**
     * Return true if addon called from backend url or
     * backend_dir is empty (not set) in configuration.
     * Else, redirect to error_404
     *
     * @access public
     * @return bool
     * @since 2.3.0
     */
    public function check_backend() {

        if ($this->app->config('backend_dir', 'HTTP') != $this->lib->url->backend_dir()) {
            $this->app->error_404('Cannot call method of class `' . get_class($this) . '` by this url.');
            return false;
        }

        return true;

    } // End func check_backend

    /**
     * Return addon configuration values
     *
     * @final
     * @access public
     * @param string $item
     * @param string $section = NULL
     * @return mixed
     */
    final public function config($item, $section = NULL) {

        if (isset($item)) {
            return $this->config->item_get($item, $section);
        }

        if (!isset($item) and isset($section)) {
            return $this->config->section_get($section);
        }

    } // end func config

    /**
     * Return addon id of this module
     *
     * @access public
     * @return string
     */
    public function id_addon() {
        return $this->id_addon;
    }

    /**
     * Get language value by expression
     * Return language prefix if item is null.
     *
     * @final
     * @access public
     * @param string $item
     * @return string
     */
    final public function language($item = NULL) {

        if (is_null($item)) {
            return $this->lib->url->language_prefix();
        }

        if (func_num_args() > 1) {
            $l_args = func_get_args();

            unset($l_args[0]);

            if (is_array($l_args[1])) {
                $l_args = $l_args[1];
            }

            return $this->language->get($item, $l_args);
        }

        return $this->language->get($item);

    } // end func language

    /* End of class module */
}

/* End of file */
?>