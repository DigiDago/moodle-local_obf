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
 * Description of obf_mock_curl
 *
 * @package    local_obf
 * @copyright  2013-2020, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 * @author jsuorsa
 */
class obf_mock_curl {
    public static $emptypngdata = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAEACAIAAADTED8xAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4QYCCiE56ohHkQAAABl0RVh0Q29tbWVudABDcmVhdGVkIHdpdGggR0lNUFeBDhcAAAH7SURBVHja7dNBDQAACMQwwL/n440GWglL1kkKvhoJMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAyAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwAAYAA4ABwABgADAAGAAMAAYAA4ABwABgADAAGAAMAAYAA4ABwABgADAAGAAMAAYAA4ABwABgADAAGAAMAAYAA4ABwABgADAAGAAMAAYAA4ABwABgADAAGAAMAAYAA4ABMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABsAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAeBaq+gE/QpErIgAAAAASUVORK5CYII=';

    public static function get_mock_curl($self) {
        // Create the mock object.
        $curl = $self->getMock('curl', array('post', 'get', 'delete'));

        // Mock HTTP POST.
        $curl->info = array('http_code' => 200);
        return $curl;
    }

    public static function add_client_test_methods($self, &$curl) {
        $curl->expects($self->once())->method(
            'post')->with($self->stringEndsWith('/test/'), $self->anything(),
            $self->anything())->will(
            $self->returnValue(json_encode(array('post' => 'works!'))));

        // Mock HTTP GET.
        $curl->expects($self->any())->method('get')->with($self->logicalOr(
            $self->stringEndsWith('/test/'),
            $self->stringEndsWith('/doesnotexist/')),
            $self->anything(), $self->anything())->will($self->returnCallback(
            function($path, $arg1, $arg2) {
                // This url exists, return a success message.
                if ($path == "/test/") {
                    return json_encode(array('get' => 'works!'));
                }

                return false; // Return false on failure (invalid url).
            }));

        // Mock HTTP DELETE.
        $curl->expects($self->once())->method('delete')->with($self->stringEndsWith('/test/'), $self->anything(),
            $self->anything())->will($self->returnValue(json_encode(array('delete' => 'works!'))));
    }

    public static function add_get_badge($self, &$curl, $clientid, $badge) {
        $curl->expects($self->once())->method(
            'get')->with($self->stringEndsWith('/badge/' . $clientid . '/' . $badge->get_id()), $self->anything(),
            $self->anything())->will(
            $self->returnValue(json_encode(
                    array('id' => $badge->get_id(), 'badge_id' => $badge->get_id(), 'description' => $badge->get_description(),
                        'image' => $badge->get_image(), 'name' => $badge->get_name()))
            ));
    }

    public static function add_issue_badge($self, &$curl, $clientid) {
        // Mock HTTP POST.
        $curl->info = array('http_code' => 200);
        $curl->expects($self->any())->method(
            'post')->with(
            $self->anything(),
            $self->anything(),
            $self->anything())->will(
            $self->returnValue(json_encode(array('post' => 'works!'))));
        $curl->rawresponse = array('Location: https://localhost.localdomain/v1/event/PHPUNIT/PHPUNITEVENTID');

    }
}
