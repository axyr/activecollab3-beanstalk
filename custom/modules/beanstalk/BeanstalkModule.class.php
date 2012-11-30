<?php

/**
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @copyright  Andreas Schempp 2012
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


class BeanstalkModule extends AngieModule
{

    /**
     * Plain module name
     *
     * @var string
     */
    protected $name = 'beanstalk';

    /**
     * Module version
     *
     * @var string
     */
    protected $version = '3.0.2';

    // ---------------------------------------------------
    //  Events and Routes
    // ---------------------------------------------------


    /**
     * Define module routes
     *
     * @param Router $r
     *
     * @return null
     */
    function defineRoutes() {
        Router::map('beanstalk_commit', '/projects/:project_slug/beanstalk/commit', array('controller' => 'beanstalk', 'action' => 'commit'));
        Router::map('beanstalk_pre_deploy', '/projects/:project_slug/beanstalk/pre_deploy', array('controller' => 'beanstalk', 'action' => 'pre_deploy'));
        Router::map('beanstalk_post_deploy', '/projects/:project_slug/beanstalk/post_deploy', array('controller' => 'beanstalk', 'action' => 'post_deploy'));
    }


    /**
     * Get module display name
     *
     * @return string
     */
    function getDisplayName() {
        return lang('Beanstalk');
    }


    /**
     * Return module description
     *
     * @param void
     *
     * @return string
     */
    function getDescription() {
        return lang('Adds Beanstalk post-deployment hook support. Can post ticket comments and track time based on your commit message.');
    }


    /**
     * Return module uninstallation message
     *
     * @param void
     *
     * @return string
     */
    function getUninstallMessage() {
        return lang('Module will be deactivated. You will no longer be able to deploy.');
    }
}

