<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Result extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
        $this->load->library('session');
        $this->load->database();
        $this->load->model('Result_model');
    }

    public function index() {
        // Only display records if an active audit was just performed in session
        $candidates = $this->session->userdata('current_audit_candidates');

        if (!empty($candidates)) {
            $score_anomalies_count = 0;
            $borderline_count      = 0;
            $cleared_count         = 0;

            foreach ($candidates as $row) {
                if ($row['grad_status_raw'] === 'uncleared') {
                    $score_anomalies_count++;
                } elseif ($row['grad_status_raw'] === 'pending') {
                    $borderline_count++;
                } elseif ($row['grad_status_raw'] === 'cleared') {
                    $cleared_count++;
                }
            }

            $data['total_audited']     = count($candidates);
            $data['score_anomalies']   = $score_anomalies_count;
            $data['borderline_scores'] = $borderline_count;
            $data['cleared_for_grad']  = $cleared_count;
            $data['candidates']        = $candidates;
            $data['department_name']   = $this->session->userdata('current_audit_dept') ?? 'Information Technology';
        } else {
            // Fresh state: empty table and zero metrics
            $data['total_audited']     = 0;
            $data['score_anomalies']   = 0;
            $data['borderline_scores'] = 0;
            $data['cleared_for_grad']  = 0;
            $data['candidates']        = array();
            $data['department_name']   = 'Information Technology';
        }

        $data['user_role']    = 'Exam Officer';
        $data['session_name'] = '2025/2026 — First Semester';

        // Clear session flash so reloading the page resets the display
        $this->session->unset_userdata('current_audit_candidates');

        $this->load->view('dashboard_view', $data);
    }

    public function process_upload() {
        $config['upload_path']   = './uploads/';
        $config['allowed_types'] = 'pdf';
        $config['max_size']      = 10240;
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('result_file')) {
            redirect('result');
        } else {
            $upload_data = $this->upload->data();
            $this->parse_broadsheet($upload_data['full_path']);
        }
    }

    private function parse_broadsheet($file_path) {
        // Clear prior table data to prevent cross-file count accumulation
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0;");
        $this->db->truncate('tableresult');
        $this->db->truncate('student');
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1;");

        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($file_path);
        $text   = $pdf->getText();

        $content = preg_replace('/\r/', '', $text);

        // 1. DYNAMICALLY DETECT ALL COURSES FROM THE HEADER
        // Matches course codes like CSC 401, COS 409, IFT 403, IFS 401, GST 413, etc.
        preg_match_all('/\b([A-Z]{3})\s*(\d{3})\b/', $content, $found_courses);
        
        $unique_course_codes = array();
        if (!empty($found_courses[0])) {
            foreach ($found_courses[0] as $raw_code) {
                $std_code = preg_replace('/\s+/', ' ', strtoupper(trim($raw_code)));
                if (!in_array($std_code, $unique_course_codes)) {
                    $unique_course_codes[] = $std_code;
                }
            }
        }

        // If no courses matched in header, default to single general course
        if (empty($unique_course_codes)) {
            $unique_course_codes = array('GEN 401');
        }

        // Register each detected course dynamically
        $course_map = array();
        foreach ($unique_course_codes as $code) {
            $unit = (stripos($code, 'GST') !== false || stripos($code, 'ENT') !== false) ? 2 : 3;
            $course_id = $this->Result_model->get_or_create_course($code, $code, $unit, 'C', 1);
            $course_map[] = array('id' => $course_id, 'code' => $code, 'unit' => $unit);
        }

        $num_courses = count($course_map);

        // 2. EXTRACT STUDENTS & THEIR DYNAMIC NUMBER OF MARKS
        // Match valid student matric numbers (RUN/... or CSC/...)
        preg_match_all('/(?:RUN\/[A-Z0-9\/]*\d{4}|CSC\/\d{4}\/?\d{0,4})\/?/i', $content, $matric_matches, PREG_OFFSET_CAPTURE);

        $candidates_for_session = array();
        $borderlines_rule = array(39, 49, 59, 69);

        if (!empty($matric_matches[0])) {
            $matches = $matric_matches[0];
            $total_m = count($matches);

            for ($i = 0; $i < $total_m; $i++) {
                $raw_matric = trim($matches[$i][0]);
                $matric = rtrim($raw_matric, '/');
                $matric = preg_replace('/RUN\/([A-Z]+)\/(\d{2})(\d{4})/i', 'RUN/$1/$2/$3', $matric);

                $start_pos = $matches[$i][1];
                $end_pos   = ($i + 1 < $total_m) ? $matches[$i + 1][1] : strlen($content);
                $block     = substr($content, $start_pos, $end_pos - $start_pos);

                // Name extraction: check before or after matric string
                $pre_start = max(0, $start_pos - 100);
                $pre_block = substr($content, $pre_start, $start_pos - $pre_start);

                $name = '';
                if (preg_match('/([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3})\s*$/m', trim($pre_block), $nm)) {
                    $name = trim($nm[1]);
                }
                if (empty($name) && preg_match('/^\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3})/m', trim(substr($block, strlen($raw_matric))), $nm)) {
                    $name = trim($nm[1]);
                }

                if (empty($name) || stripos($name, 'Semester') !== false || stripos($name, 'Page') !== false) {
                    $name = 'Candidate ' . ($i + 1);
                }

                // Extract all numbers in student's block
                preg_match_all('/\b\d{1,3}\b/', $block, $num_matches);
                $numbers = array_map('intval', $num_matches[0]);

                $clean_numbers = array();
                foreach ($numbers as $num) {
                    if ($num <= 100) {
                        $clean_numbers[] = $num;
                    }
                }

                $student_id = $this->Result_model->get_or_create_student($matric, $name, 1, 400);

                $tur = 0;
                $tup = 0;
                $total_grade_points = 0.0;
                $flags = array();
                $has_error = false;
                $has_borderline = false;

                // Case A: Single Course Sheet with Total (e.g., CA Exam Total Grade)
                if ($num_courses === 1) {
                    $c_unit    = $course_map[0]['unit'];
                    $course_id = $course_map[0]['id'];
                    $c_code    = $course_map[0]['code'];

                    // If CA and Exam exist, sum them or pick total
                    if (count($clean_numbers) >= 2) {
                        $total = (count($clean_numbers) >= 3 && $clean_numbers[2] <= 100 && $clean_numbers[2] == ($clean_numbers[0] + $clean_numbers[1]))
                            ? $clean_numbers[2]
                            : ($clean_numbers[0] + $clean_numbers[1]);
                    } else {
                        $total = isset($clean_numbers[0]) ? $clean_numbers[0] : 0;
                    }

                    $tur += $c_unit;
                    if ($total >= 40) $tup += $c_unit;

                    if ($total >= 70)      $gp = 5.0;
                    elseif ($total >= 60) $gp = 4.0;
                    elseif ($total >= 50) $gp = 3.0;
                    elseif ($total >= 45) $gp = 2.0;
                    elseif ($total >= 40) $gp = 1.0;
                    else                  $gp = 0.0;

                    $total_grade_points += ($gp * $c_unit);

                    $anomaly_flag = 'Normal';
                    if ($total === 0) {
                        $anomaly_flag = 'Zero Score (' . $c_code . ')';
                        $flags[] = array('type' => 'error', 'label' => $anomaly_flag);
                        $has_error = true;
                    } elseif (in_array($total, $borderlines_rule)) {
                        $anomaly_flag = 'Borderline ' . $total . ' (' . $c_code . ')';
                        $flags[] = array('type' => 'borderline', 'label' => $anomaly_flag);
                        $has_borderline = true;
                    } elseif ($total < 40) {
                        $anomaly_flag = 'Failed (' . $c_code . ')';
                        $flags[] = array('type' => 'error', 'label' => $anomaly_flag);
                        $has_error = true;
                    }

                    $this->Result_model->save_result($student_id, $course_id, $total, $anomaly_flag);
                } 
                // Case B: Multi-Course Sheet (pairs of CA + Exam for each course detected)
                else {
                    for ($c = 0; $c < $num_courses; $c++) {
                        $course_id = $course_map[$c]['id'];
                        $c_unit    = $course_map[$c]['unit'];
                        $c_code    = $course_map[$c]['code'];
                        
                        $ca_idx   = $c * 2;
                        $exam_idx = ($c * 2) + 1;

                        $ca   = isset($clean_numbers[$ca_idx]) ? $clean_numbers[$ca_idx] : 0;
                        $exam = isset($clean_numbers[$exam_idx]) ? $clean_numbers[$exam_idx] : 0;
                        $total = $ca + $exam;

                        $tur += $c_unit;
                        if ($total >= 40) $tup += $c_unit;

                        if ($total >= 70)      $gp = 5.0;
                        elseif ($total >= 60) $gp = 4.0;
                        elseif ($total >= 50) $gp = 3.0;
                        elseif ($total >= 45) $gp = 2.0;
                        elseif ($total >= 40) $gp = 1.0;
                        else                  $gp = 0.0;

                        $total_grade_points += ($gp * $c_unit);

                        $anomaly_flag = 'Normal';
                        if ($total === 0) {
                            $anomaly_flag = 'Zero Score (' . $c_code . ')';
                            $flags[] = array('type' => 'error', 'label' => $anomaly_flag);
                            $has_error = true;
                        } elseif (in_array($total, $borderlines_rule)) {
                            $anomaly_flag = 'Borderline ' . $total . ' (' . $c_code . ')';
                            $flags[] = array('type' => 'borderline', 'label' => $anomaly_flag);
                            $has_borderline = true;
                        } elseif ($total < 40) {
                            $anomaly_flag = 'Failed (' . $c_code . ')';
                            $flags[] = array('type' => 'error', 'label' => $anomaly_flag);
                            $has_error = true;
                        }

                        $this->Result_model->save_result($student_id, $course_id, $total, $anomaly_flag);
                    }
                }

                $cgpa = ($tur > 0) ? round($total_grade_points / $tur, 2) : 0.00;

                if ($has_error) {
                    $grad_status_raw   = 'uncleared';
                    $grad_status_label = 'Uncleared';
                } elseif ($has_borderline) {
                    $grad_status_raw   = 'pending';
                    $grad_status_label = 'Pending Review';
                } else {
                    $flags[] = array('type' => 'clean', 'label' => 'Clean');
                    $grad_status_raw   = 'cleared';
                    $grad_status_label = 'Cleared (Pass)';
                }

                $candidates_for_session[] = array(
                    'id'                => $student_id,
                    'matric'            => $matric,
                    'name'              => $name,
                    'tur'               => $tur,
                    'tup'               => $tup,
                    'cgpa'              => $cgpa,
                    'flags'             => $flags,
                    'grad_status_raw'   => $grad_status_raw,
                    'grad_status_label' => $grad_status_label
                );
            }
        }
        
        // Store active batch in session
        $this->session->set_userdata('current_audit_candidates', $candidates_for_session);
        $this->session->set_userdata('current_audit_dept', 'Information Technology');

        redirect('result');
    }

    // Add this right before the last closing brace "}" of the class
    public function get_student_sheet($student_id) {
        $this->db->select('c.courseCode, c.unit, r.score, r.status');
        $this->db->from('tableresult r');
        $this->db->join('course c', 'c.id = r.courseId', 'inner');
        $this->db->where('r.studentId', $student_id);
        $query = $this->db->get();
        
        echo json_encode($query->result_array());
        exit;
    }

} // <--- This is the final closing brace of Result.php
