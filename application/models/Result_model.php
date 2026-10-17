<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Result_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // ==========================================
    // SECTION 1: YOUR ORIGINAL PARSER FUNCTIONS
    // ==========================================

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


    // ==========================================
    // SECTION 2: NEW MULTI-DEPARTMENT FUNCTIONS
    // ==========================================

    // Fetch data for the Dashboard tables based on context
    public function get_audit_candidates($department, $level, $academic_session, $semester) {
        $this->db->where(array(
            'department'       => $department,
            'level'            => $level,
            'academic_session' => $academic_session,
            'semester'         => $semester
        ));
        $query = $this->db->get('faculty_audit_sessions');
        
        // If no upload exists for this specific Department + Level + Semester, return empty immediately
        if ($query->num_rows() == 0) {
            return array();
        }
        
        $session_id = $query->row()->id;
        
        $this->db->where('audit_session_id', $session_id);
        $students_query = $this->db->get('faculty_audit_students');
        
        $candidates = array();
        foreach ($students_query->result_array() as $row) {
            $row['flags'] = json_decode($row['flags_json'], true);
            $candidates[] = $row;
        }
        
        return $candidates;
    }

    public function save_audit_data($department, $level, $academic_session, $semester, $user_id, $candidates) {
        $this->db->trans_start();

        // 1. Check if a session already exists for this exact Department + Level + Session + Semester
        $this->db->where(array(
            'department'       => $department,
            'level'            => $level,
            'academic_session' => $academic_session,
            'semester'         => $semester
        ));
        $existing = $this->db->get('faculty_audit_sessions');

        if ($existing->num_rows() > 0) {
            $old_session_id = $existing->row()->id;
            
            // Delete old grades & students for this specific semester/level
            $this->db->where('audit_session_id', $old_session_id);
            $old_students = $this->db->get('faculty_audit_students')->result_array();
            foreach ($old_students as $stu) {
                $this->db->where('student_id', $stu['id']); // <-- Changed to student_id
                $this->db->delete('faculty_audit_grades');
            }
            $this->db->where('audit_session_id', $old_session_id);
            $this->db->delete('faculty_audit_students');

            $this->db->where('id', $old_session_id);
            $this->db->delete('faculty_audit_sessions');
        }

        // 2. Insert the new audit session with the active semester
        $session_data = array(
            'department'       => $department,
            'level'            => $level,
            'academic_session' => $academic_session,
            'semester'         => $semester,
            'uploaded_by'      => $user_id
        );
        $this->db->insert('faculty_audit_sessions', $session_data);
        $session_id = $this->db->insert_id();

        // 3. Insert each student and their courses
        foreach ($candidates as $candidate) {
            $student_data = array(
                'audit_session_id'  => $session_id,
                'matric'            => $candidate['matric'],
                'name'              => $candidate['name'],
                'tur'               => $candidate['tur'],
                'tup'               => $candidate['tup'],
                'cgpa'              => $candidate['cgpa'],
                'flags_json'        => json_encode($candidate['flags']),
                'grad_status_raw'   => $candidate['grad_status_raw'],
                'grad_status_label' => $candidate['grad_status_label']
            );
            $this->db->insert('faculty_audit_students', $student_data);
            $student_id = $this->db->insert_id();

            if (!empty($candidate['courses'])) {
                foreach ($candidate['courses'] as $course) {
                    $grade_data = array(
                        'student_id'  => $student_id, // <-- Changed to student_id
                        'course_code' => $course['courseCode'],
                        'unit'        => $course['unit'],
                        'score'       => $course['score'],
                        'status'      => $course['status']
                    );
                    $this->db->insert('faculty_audit_grades', $grade_data);
                }
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}