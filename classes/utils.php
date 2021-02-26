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
 * Utils class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq;

defined('MOODLE_INTERNAL') || die();

/**
 * Utils class.
 *
 * This class has various static helper methods.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Sort an array.
     *
     * @param array $inputarray Array to sort.
     * @param string $sorton The value to sort the array by.
     * @param string $direction The direction to sort in.
     * @return array $inputarray the sorted arrays.
     */
    public static function sort(array $inputarray, string $sorton, string $direction):array {

        uasort($inputarray, function($a, $b) use ($direction, $sorton) {
            if ($direction == 'asc') {
                if (gettype($a->{$sorton}) == 'string') {
                    return strcasecmp($a->{$sorton}, $b->{$sorton});
                } else {
                    // The spaceship operator is used for comparing two expressions.
                    // It returns -1, 0 or 1 when $a is respectively less than, equal to, or greater than $b.
                    return $a->{$sorton} <=> $b->{$sorton};
                }
            } else {
                if (gettype($a->{$sorton}) == 'string') {
                    return strcasecmp($b->{$sorton}, $a->{$sorton});
                } else {
                    // The spaceship operator is used for comparing two expressions.
                    // It returns -1, 0 or 1 when $a is respectively less than, equal to, or greater than $b.
                    return $b->{$sorton} <=> $a->{$sorton};
                }
            }

        });

        return $inputarray;
    }

    /**
     * Sort an array of arrays/objects by multiple values.
     *
     * @param array $inputarray Array of quizzes to sort.
     * @param array $sorton Associative array to sort by in the format field => direction.
     * @return array $inputarray the sorted array.
     */
    public static function multi_sort(array $inputarray, array $sorton):array {
        // Convert to an array of arrays if required.
        $element = reset($inputarray);
        if (gettype($element) == 'object') {
            $makearray = array();
            foreach ($inputarray as $object) {
                $makearray[] = (array)$object;
            }
            $inputarray = $makearray;
        }

        // Take sort on array and format it for passing to array_multisort.
        $sortvariables = array();
        foreach ($sorton as $sort => $direction) {
            $sortvariables[] = array_column($inputarray, $sort);

            if (strtolower($direction) == 'asc') {
                $dir = SORT_ASC;
            } else {
                $dir = SORT_DESC;
            }

            $sortvariables[] = $dir;
        }
        $sortvariables[] = &$inputarray;

        // As we have a dynamic array of variables we call array_multisort
        // via call_user_func_array.
        call_user_func_array('array_multisort', $sortvariables);

        // Convert back to an array of objects if needed.
        if (gettype($element) == 'object') {
            $makeobject = array();
            foreach ($inputarray as $object) {
                $makeobject[] = (object)$object;
            }
            $inputarray = $makeobject;
        }

        return $inputarray;
    }
}
