<?php
/*
    * Plugin Name: WPP Data Merge
    * Description: Conecta los datos de CINMCO ERP y sincroniza post creando o actualizando en informacion requerida en WP.
    * Plugin URI: https://www.instagram.com/playtic_pasto/
    * Version: 0.0.1-alpha.2
    * Author: PlayTIC
    * Author URI: hhttps://www.instagram.com/playtic_pasto/
    * Licence: GPLv2 or later
    * Text Domain: wpp-data-merge
 */

##########################################################################
#                                                                        #
# Original Copyright (C) 2015  Dr. Max V.                                #
# Modifications Copyright (C) 2018  Roy Orbison                          #
#                                                                        #
# This program is free software: you can redistribute it and/or modify   #
# it under the terms of the GNU General Public License as published by   #
# the Free Software Foundation, either version 3 of the License, or      #
# (at your option) any later version.                                    #
#                                                                        #
# This program is distributed in the hope that it will be useful,        #
# but WITHOUT ANY WARRANTY; without even the implied warranty of         #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          #
# GNU General Public License for more details.                           #
#                                                                        #
# You should have received a copy of the GNU General Public License      #
# along with this program.  If not, see <https://www.gnu.org/licenses/>. #
#                                                                        #
##########################################################################

declare(strict_types=1);

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/vendor/autoload.php';

use WPDM\Bootstrap\Plugin;


define('WPDM_PATH', plugin_dir_path(__FILE__));
define('WPDM_URL', plugin_dir_url(__FILE__));
define('WPDM_FILE', __FILE__);
define('WPDM_VERSION', get_file_data(__FILE__, ['Version' => 'Version'])['Version']);


Plugin::init();
