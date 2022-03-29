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
 * Lists all the users enrolled to course in which the teacher is also enrolled.
 *
 * @copyright 2021 Grzegorz Bębenista  <gbebenista@ahe.lodz.pl>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package block_studentslist
 */

require_once('../../config.php');
require_once('studentslist_table.php');
require_once('studentslist_form.php');

//set params to the URL
$page = optional_param('page', 0, PARAM_INT); // Which page to show.
$filtercategory = optional_param('category', '0', PARAM_RAW); // Which category to show
$student = optional_param('studentname','', PARAM_RAW);

require_login();
$context = context_system::instance();
$pageurl = new \moodle_url('/blocks/studentslist/view.php', array('category' => $filtercategory, 'studentname' => $student));
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
//set navbar
$PAGE->navbar->add(get_string('mymoodle', 'core_my'), new moodle_url('/my'));
$PAGE->navbar->add(get_string('studentslist', 'block_studentslist'));
//set other things related to page
$PAGE->set_title(get_string('studentslist', 'block_studentslist'));
$PAGE->set_heading(get_string('studentslist', 'block_studentslist'));
$PAGE->set_pagetype('studentslist-view');

//create an instance of the form
$form = new studentslist_form(null, array('categorychoice' => $filtercategory));
if($form->is_cancelled()) redirect(new moodle_url('/blocks/studentslist/view.php'));

//output the content of the page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('welcome', 'block_studentslist'));
$form->display();

//create an instance of the table
$studentslisttable = new \block_studentslist\studentslist_table($USER->id, $filtercategory,$student);
$studentslisttable->baseurl = $pageurl;
$studentslisttable->out(20, false);

echo $OUTPUT->footer();
