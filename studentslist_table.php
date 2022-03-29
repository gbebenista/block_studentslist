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
 * Class used to fetch participants based on a filterset.
 *
 * @package    block_studentslist
 * @copyright  2021 Grzegorz Bębenista <gbebenista@ahe.lodz.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 namespace block_studentslist;

 defined('MOODLE_INTERNAL') || die();
 require_once($CFG->libdir . '/tablelib.php');
 
 /**
  * Table to display list of teacher's students
  *
  * @copyright  2021 Grzegorz Bębenista <gbebenista@ahe.lodz.pls>
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */
 class studentslist_table extends \table_sql {
    
    //public function __construct(string $filter = '', int $resultfilter = null) {
    public function __construct($userid, $categoryid, $studentname) {
        global $DB;

        parent::__construct("studentslist-table-$userid");

        $columnheaders = [
            'fullname'  => get_string('fullname'),
            'courses'   => get_string('courses'),
        ];
        $this->define_columns(array_keys($columnheaders));
        $this->define_headers(array_values($columnheaders));

        // The name column is a header.
        $this->define_header_column('fullname');

        // This table is not collapsible.
        $this->collapsible(false);

        // Allow pagination.
        $this->pageable(true);

        //$this->initialbars(true);

        // Allow sorting. Default to sort by lastname DESC.
        $this->sortable(true, 'lastname', SORT_ASC);

        //Add filtering.
        $where='';
        $params = [];

        $params['teacherid'] = $userid;

        if ($categoryid != '0') {
            $categories = explode(",",$categoryid);
            list($insql, $inparams) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED, 'category');
            $where = "mc.category $insql AND ";
            $params +=$inparams;
        }
        if($studentname !='') {
            $where = $where."lower(concat(mu.firstname,' ', mu.lastname)) like lower(:studentname) AND"; 
            $params['studentname'] = '%' . $DB->sql_like_escape($studentname) . '%';
        }

        $this->set_sql('', '', $where, $params);
    }

    /**
     * Query the db. Store results in the table object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar. Bar
     * will only be used if there is a fullname column defined for the table.
     */
     public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        // Fetch the attempts.
        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = "ORDER BY $sort";
        }

        $where = '';
        if (!empty($this->sql->where)) {
            $where = "{$this->sql->where}";
        }

        $params = '';
        if (!empty($this->sql->params)) {
            $params = $this->sql->params;
        }

        $sql =    "SELECT 
                        mu.id as userid,
                        mu.firstname,
                        mu.lastname,
                        string_agg(mc.fullname, '|') as courses, string_agg(mc.id::text, '|') as courseid
                    FROM 
                        {user} mu, 
                        {course} mc, 
                        {context} mctx, 
                        {role_assignments} mra 
                    WHERE 
                        mctx.instanceid = mc.id and 
                        mra.contextid = mctx.id and 
                        mu.id = mra.userid and 
                        mra.roleid = 5 and
                        {$where} 
                        mc.id in (
                            select mc.instanceid 
                            from {role_assignments} mra, {context} mc 
                            where mra.contextid = mc.id and mra.userid = $params[teacherid] and mra.roleid = 12
                            group by mc.instanceid)
                    GROUP BY mu.id, mu.lastname, mu.firstname {$sort}";
        
        $this->pagesize($pagesize, $DB->count_records_sql("SELECT count(distinct mu.id)
        from mdl_user mu, mdl_course mc, mdl_context mctx, mdl_role_assignments mra 
        where mctx.instanceid = mc.id and mra.contextid = mctx.id and mu.id = mra.userid and mra.roleid = 5 and {$where}
        mc.id in (
        select mc.instanceid 
        from mdl_role_assignments mra, mdl_context mc 
        where mra.contextid = mc.id and mra.userid = $params[teacherid] and mra.roleid = 12
        group by mc.instanceid)", $this->sql->params));

        if (!$this->is_downloading()) {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params, $this->get_page_start(), $this->get_page_size());
        } else {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params);
        }
    }

    /**
     * Format the fullname cell.
     *
     * @param   \stdClass $row
     * @return  string
     */
     public function col_fullname($row) : string {
        if (empty($row->userid)) {
            return '';
        }
        return $row->firstname.' '.$row->lastname;
    }

    /**
     * Format the courses cell.
     *
     * @param   \stdClass $row
     * @return  string
     */
     public function col_courses($row) {
        if(empty($row->courses)){
            return '';
        }

        $courses = explode("|",$row->courses);
        $coursesid = explode("|",$row->courseid);
        $courselinks = array();

        foreach(array_combine($courses,$coursesid) as $courses => $coursesid){
            array_push($courselinks, \html_writer::link(new \moodle_url('/user/view.php', array('id'=>$row->userid, 'course'=>$coursesid)), $courses));
         }
        return implode(", ", $courselinks);
    }


 }


?>