<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_obf
 * @copyright  2013-2020, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugins = array(
    'obf-criteria-markdown' => array(
        'files' => array(
            'jquerymodule/jquery-criteria-markdown.js',
        ),
    ),
    'obf-emailverifier' => array(
        'files' => array(
            'emailverifier/emailverifier.js',
        ),
    ),
    'obf-simplemde' => array(
        'files' => array(
            'simplemde/simplemde.min.js',
        ),
    ),
    'obf-chooseprogram' => array(
        'files' => array(
            'chooseprogram/chooseprogram.js',
        ),
    )
);
