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
 * Class used to create form for choosing the categories.
 *
 * @package    block_studentslist
 * @copyright  2021 Grzegorz Bębenista <gbebenista@ahe.lodz.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

 /**
  * Form to display the categories.
  *
  * @copyright  2021 Grzegorz Bębenista <gbebenista@ahe.lodz.pls>
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */
class studentslist_form extends moodleform {

    /**
     * Prepares the form.
     *
     */
    public function definition() {
        global $CFG;
       
        $mform = $this->_form;   
        $mform-> addElement('hidden', 'categorychoice', $this->_customdata['categorychoice']);
        $mform-> setType('categorychoice', PARAM_RAW);
        $mform-> setDefault('category', $this->_customdata['categorychoice']);
        $mform-> addElement('text','studentname' , get_string('studentname', 'block_studentslist'));
        $mform-> setType('studentname', PARAM_RAW); 
        $mform-> addElement('select', 'category', get_string('category'),$this->prepare_form_values());
        $this->add_action_buttons();      
    }

    /**
     * Query the db. Prepare values shown in the form.
     *
     * @return  array
     */
    function prepare_form_values(){
        global $DB;
        $allcourses = get_string('allcourses', 'block_studentslist');
        $sql = "SELECT '0' id, '$allcourses' as categoryname
                UNION ALL
                SELECT (SELECT coalesce(string_agg(mcc2.id::text, ','), mcc.id::text) 
                FROM mdl_course_categories mcc2 WHERE mcc2.parent = mcc.id) id,
                concat((SELECT coalesce(concat(mcc2.name,' - '), '') 
                        FROM mdl_course_categories mcc2
                        WHERE mcc2.id = mcc.parent), mcc.name) AS categoryname      
                FROM mdl_course_categories mcc 
                WHERE mcc.parent in (49, 52)
                ORDER BY categoryname DESC";
        $categories = $DB->get_records_sql_menu($sql);
        return $categories;
    }

}
?>