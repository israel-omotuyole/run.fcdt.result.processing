<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Result_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get or create a course in the course table
     */
    public function get_or_create_course($course_code, $title, $unit, $status = 'C', $dept_id = 1) {
        $query = $this->db->get_where('course', array('courseCode' => $course_code));
        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        $data = array(
            'courseCode' => $course_code,
            'title'      => $title,
            'deptId'     => $dept_id,
            'unit'       => $unit,
            'status'     => $status
        );
        $this->db->insert('course', $data);
        return $this->db->insert_id();
    }

    /**
     * Get or create a student in the student table
     */
    public function get_or_create_student($matric, $name, $dept_id = 1, $level = 400) {
        $query = $this->db->get_where('student', array('matricNo' => $matric));
        if ($query->num_rows() > 0) {
            $student_id = $query->row()->id;
            // Update name if it was previously empty or truncated
            if (!empty($name) && strlen($name) > strlen($query->row()->studentName)) {
                $this->db->where('id', $student_id)->update('student', array('studentName' => $name));
            }
            return $student_id;
        }

        $data = array(
            'matricNo'    => $matric,
            'studentName' => $name,
            'deptId'      => $dept_id,
            'sessionId'   => 1,
            'level'       => (int)$level
        );
        $this->db->insert('student', $data);
        return $this->db->insert_id();
    }

    /**
     * Save/update score in tableresult
     */
    public function save_result($student_id, $course_id, $total, $status) {
        $where = array('studentId' => $student_id, 'courseId' => $course_id);
        $query = $this->db->get_where('tableresult', $where);

        $record = array(
            'studentId' => $student_id,
            'courseId'  => $course_id,
            'score'     => $total,
            'status'    => $status
        );

        if ($query->num_rows() > 0) {
            $this->db->where($where);
            return $this->db->update('tableresult', $record);
        } else {
            return $this->db->insert('tableresult', $record);
        }
    }

    /**
     * Fetch complete audit data aggregated over all registered courses
     */
    public function get_audited_dashboard_data() {
        $sql = "
            SELECT 
                s.id,
                s.matricNo AS matric,
                s.studentName AS name,
                COALESCE(SUM(c.unit), 0) AS tur,
                COALESCE(SUM(CASE WHEN r.score >= 40 THEN c.unit ELSE 0 END), 0) AS tup,
                -- 5.0 CGPA scale calculation: sum(unit * grade_point) / sum(unit)
                ROUND(
                    SUM(
                        c.unit * (
                            CASE 
                                WHEN r.score >= 70 THEN 5.0
                                WHEN r.score >= 60 THEN 4.0
                                WHEN r.score >= 50 THEN 3.0
                                WHEN r.score >= 45 THEN 2.0
                                WHEN r.score >= 40 THEN 1.0
                                ELSE 0.0
                            END
                        )
                    ) / NULLIF(SUM(c.unit), 0), 2
                ) AS cgpa,
                COALESCE(MIN(r.score), 100) AS min_score,
                GROUP_CONCAT(DISTINCT r.status SEPARATOR '||') AS flags_str,
                GROUP_CONCAT(CONCAT(c.courseCode, ':', r.score) ORDER BY c.courseCode SEPARATOR ', ') AS breakdown
            FROM student s
            INNER JOIN tableresult r ON r.studentId = s.id
            INNER JOIN course c ON c.id = r.courseId
            GROUP BY s.id, s.matricNo, s.studentName
            ORDER BY s.id ASC
        ";

        return $this->db->query($sql)->result_array();
    }
}