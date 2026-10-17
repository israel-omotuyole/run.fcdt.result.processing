<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Result extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
        $this->load->library('session');
        $this->load->database();
        $this->load->model('Result_model');

        // Security Lock
        if (!$this->session->userdata('logged_in') || $this->session->userdata('role') !== 'exam_officer') {
            redirect('auth');
        }
    }

    public function index() {
        // 1. Fetch the active session, semester, level, and department from session data first
        $active_level    = $this->session->userdata('active_level') ?? 400;
        $active_dept     = $this->session->userdata('active_dept') ?? 'Information Technology';
        $active_session  = $this->session->userdata('active_session') ?? '2025/2026';
        $active_semester = $this->session->userdata('active_semester') ?? 'First Semester';

        // 2. Populate $data array for the view
        $data['active_level']    = $active_level;
        $data['active_dept']     = $active_dept;
        $data['department_name'] = $active_dept;
        $data['active_session']  = $active_session;
        $data['active_semester'] = $active_semester;
        $data['user_role']       = 'Exam Officer';

        // 3. Read the audited candidates from the MySQL Database using all 4 parameters accurately
        $candidates = $this->Result_model->get_audit_candidates(
            $active_dept, 
            $active_level, 
            $active_session, 
            $active_semester
        );

        // 4. Calculate Top Metrics
        if (!empty($candidates)) {
            $score_anomalies_count = 0;
            $borderline_count      = 0;
            $cleared_count         = 0;

            foreach ($candidates as $row) {
                if ($row['grad_status_raw'] === 'uncleared' || $row['grad_status_raw'] === 'error') {
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
        } else {
            $data['total_audited']     = 0;
            $data['score_anomalies']   = 0;
            $data['borderline_scores'] = 0;
            $data['cleared_for_grad']  = 0;
            $data['candidates']        = array();
        }

        // 5. Load the shared dashboard view
        $this->load->view('dashboard_view', $data);
    }

    public function process_upload() {
        // Explicitly load Composer autoloader for PDF parser
        require_once FCPATH . 'vendor/autoload.php';

        // Configure file upload settings
        $config['upload_path']   = './uploads/';
        $config['allowed_types'] = 'pdf';
        $config['max_size']      = 10240; // 10MB

        $this->load->library('upload', $config);

        // Note: Make sure 'result_file' matches the name="" attribute of your <input type="file"> in dashboard_view.php
        // If your input is named 'broadsheet_file' or 'result_file', check below:
        $file_field = isset($_FILES['broadsheet_file']) ? 'broadsheet_file' : 'result_file';

        if (!$this->upload->do_upload($file_field)) {
            $error = $this->upload->display_errors('', '');
            $this->session->set_flashdata('error', 'Upload failed: ' . $error);
            redirect('result');
            return;
        }

        $file_data = $this->upload->data();
        $file_path = $file_data['full_path'];

        // Call parse_broadsheet to extract students and save to database!
        $this->parse_broadsheet($file_path);
    }

    private function parse_broadsheet($file_path) {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($file_path);
        $text   = $pdf->getText();
        $content = preg_replace('/\r/', '', $text);

        // 1. DYNAMICALLY DETECT ALL COURSES
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
        if (empty($unique_course_codes)) {
            $unique_course_codes = array('GEN 401');
        }

        $course_map = array();
        foreach ($unique_course_codes as $code) {
            $unit = (stripos($code, 'GST') !== false || stripos($code, 'ENT') !== false) ? 2 : 3;
            $course_map[] = array('code' => $code, 'unit' => $unit);
        }
        $num_courses = count($course_map);

        // SANITIZE CONTENT
        $noise_patterns = array(
            '/\(out of 30\)/i', '/\(out of 70\)/i', '/\(out of 100\)/i', 
            '/\(\/30\)/i', '/\(\/70\)/i', '/\(\/100\)/i',
            '/CA Score/i', '/Exam Score/i', '/Total Score/i', 
            '/Student Full Name/i', '/Full Name/i', '/Matric Number/i',
            '/Course Title/i', '/Credit Units/i', '/Semester\/Level/i',
            '/Grade/i', '/Status/i', '/S\/N/i'
        );
        $clean_content = preg_replace($noise_patterns, ' ', $content);

        // 2. EXTRACT STUDENTS & MARKS
        preg_match_all('/(?:RUN\/[A-Z0-9\/]*\d{4}\/?|CSC\/\d{4}\/?\d{4}\/?)/i', $clean_content, $matric_matches, PREG_OFFSET_CAPTURE);

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
                $end_pos   = ($i + 1 < $total_m) ? $matches[$i + 1][1] : strlen($clean_content);
                $block     = substr($clean_content, $start_pos, $end_pos - $start_pos);

                $pre_start = max(0, $start_pos - 120);
                $pre_block = substr($clean_content, $pre_start, $start_pos - $pre_start);

                $name = '';
                if (preg_match('/([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3})\s*$/m', rtrim($pre_block), $nm)) {
                    $name = trim($nm[1]);
                }
                if (empty($name) && preg_match('/^\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3})/m', ltrim(substr($block, strlen($raw_matric))), $nm)) {
                    $name = trim($nm[1]);
                }
                if (empty($name)) {
                    $name = 'Candidate ' . ($i + 1);
                }

                preg_match_all('/\b\d{1,3}\b/', $block, $num_matches);
                $numbers = array_map('intval', $num_matches[0]);

                $clean_numbers = array();
                foreach ($numbers as $num) {
                    if ($num <= 100) $clean_numbers[] = $num;
                }

                $tur = 0;
                $tup = 0;
                $total_grade_points = 0.0;
                $flags = array();
                $has_error = false;
                $has_borderline = false;
                $student_courses = array();

                if ($num_courses === 1) {
                    $c_unit = $course_map[0]['unit'];
                    $c_code = $course_map[0]['code'];

                    if (count($clean_numbers) >= 2) {
                        $total = (count($clean_numbers) >= 3 && $clean_numbers[2] <= 100 && $clean_numbers[2] == ($clean_numbers[0] + $clean_numbers[1]))
                            ? $clean_numbers[2]
                            : ($clean_numbers[0] + $clean_numbers[1]);
                    } else {
                        $total = isset($clean_numbers[0]) ? $clean_numbers[0] : 0;
                    }

                    $tur += $c_unit;
                    if ($total >= 40) $tup += $c_unit;

                    if ($total >= 70) $gp = 5.0;
                    elseif ($total >= 60) $gp = 4.0;
                    elseif ($total >= 50) $gp = 3.0;
                    elseif ($total >= 45) $gp = 2.0;
                    elseif ($total >= 40) $gp = 1.0;
                    else $gp = 0.0;

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

                    $student_courses[] = array(
                        'courseCode' => $c_code,
                        'unit'       => $c_unit,
                        'score'      => $total,
                        'status'     => $anomaly_flag
                    );
                } else {
                    for ($c = 0; $c < $num_courses; $c++) {
                        $c_unit = $course_map[$c]['unit'];
                        $c_code = $course_map[$c]['code'];
                        
                        $ca_idx   = $c * 2;
                        $exam_idx = ($c * 2) + 1;

                        $ca    = isset($clean_numbers[$ca_idx]) ? $clean_numbers[$ca_idx] : 0;
                        $exam  = isset($clean_numbers[$exam_idx]) ? $clean_numbers[$exam_idx] : 0;
                        $total = $ca + $exam;

                        $tur += $c_unit;
                        if ($total >= 40) $tup += $c_unit;

                        if ($total >= 70) $gp = 5.0;
                        elseif ($total >= 60) $gp = 4.0;
                        elseif ($total >= 50) $gp = 3.0;
                        elseif ($total >= 45) $gp = 2.0;
                        elseif ($total >= 40) $gp = 1.0;
                        else $gp = 0.0;

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

                        $student_courses[] = array(
                            'courseCode' => $c_code,
                            'unit'       => $c_unit,
                            'score'      => $total,
                            'status'     => $anomaly_flag
                        );
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
                    'matric'            => $matric,
                    'name'              => $name,
                    'tur'               => $tur,
                    'tup'               => $tup,
                    'cgpa'              => $cgpa,
                    'flags'             => $flags,
                    'grad_status_raw'   => $grad_status_raw,
                    'grad_status_label' => $grad_status_label,
                    'courses'           => $student_courses // Array of courses to be saved
                );
            }
        }

        // Save to Database via Result_model using active session context
        $active_level    = $this->session->userdata('active_level') ?? 400;
        $active_dept     = $this->session->userdata('active_dept') ?? 'Information Technology';
        $active_session  = $this->session->userdata('active_session') ?? '2025/2026';
        $active_semester = $this->session->userdata('active_semester') ?? 'First Semester';
        $user_id         = $this->session->userdata('user_id');

        if (!empty($candidates_for_session)) {
            $this->Result_model->save_audit_data(
                $active_dept, 
                $active_level, 
                $active_session, 
                $active_semester,
                $user_id, 
                $candidates_for_session
            );
            $this->session->set_flashdata('success', 'Result broadsheet for ' . $active_level . 'L (' . $active_semester . ') processed and saved permanently.');
        } else {
            $this->session->set_flashdata('error', 'No candidates found in the document.');
        }

        redirect('result');
    }
    
    public function get_student_sheet($student_id) {
        // Force header to JSON so the browser parses it correctly
        $this->output->set_content_type('application/json');
        
        $this->db->select('course_code as courseCode, unit, score, status');
        $this->db->from('faculty_audit_grades');
        $this->db->where('student_id', (int)$student_id);
        $query = $this->db->get();
        
        echo json_encode($query->result_array());
        exit;
    }

    public function export_broadsheet() {
        // Explicitly load Composer autoloader for PhpSpreadsheet
        require_once FCPATH . 'vendor/autoload.php';

        // 1. Define active scope
        $active_level = $this->session->userdata('active_level') ?? 400;
        $active_dept  = $this->session->userdata('active_dept') ?? 'Information Technology';
        $active_session = $this->session->userdata('active_session') ?? '2025/2026';

        // 2. Fetch candidates directly from the database
        $candidates = $this->Result_model->get_audit_candidates($active_dept, $active_level, $active_session);

        if (empty($candidates)) {
            show_error('No active audit data found to export. Please upload a result sheet first.', 404, 'Export Failed');
            return;
        }

        $filename = 'Senate_Broadsheet_' . str_replace(' ', '_', $active_dept) . '_' . date('Ymd_Hi') . '.xlsx';

        // Initialize PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Audit Broadsheet');

        $headers = ['S/N', 'Matric Number', 'Student Name', 'TUR', 'TUP', 'CGPA', 'Graduation Status', 'Audit Flags'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $row_num = 2;
        $sn = 1;
        foreach ($candidates as $row) {
            $flag_labels = array();
            foreach ($row['flags'] as $flag) {
                $flag_labels[] = $flag['label'];
            }
            $flags_str = implode(' | ', $flag_labels);

            $sheet->setCellValue('A' . $row_num, $sn++);
            $sheet->setCellValue('B' . $row_num, $row['matric']);
            $sheet->setCellValue('C' . $row_num, $row['name']);
            $sheet->setCellValue('D' . $row_num, $row['tur']);
            $sheet->setCellValue('E' . $row_num, $row['tup']);
            $sheet->setCellValue('F' . $row_num, number_format($row['cgpa'], 2));
            $sheet->setCellValue('G' . $row_num, $row['grad_status_label']);
            $sheet->setCellValue('H' . $row_num, $flags_str);
            $row_num++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function change_context() {
        if ($this->input->post('active_dept')) {
            $this->session->set_userdata('active_dept', $this->input->post('active_dept'));
        }
        if ($this->input->post('active_level')) {
            $this->session->set_userdata('active_level', $this->input->post('active_level'));
        }
        if ($this->input->post('active_session')) {
            $this->session->set_userdata('active_session', $this->input->post('active_session'));
        }
        if ($this->input->post('active_semester')) {
            $this->session->set_userdata('active_semester', $this->input->post('active_semester'));
        }
        
        // Redirect back to the controller that called it
        redirect($this->router->fetch_class());
    }

    public function reset_session_records() {
        // Ensure only Exam Officers can trigger a reset
        if ($this->session->userdata('role') !== 'exam_officer') {
            show_error('Unauthorized action.', 403, 'Access Denied');
            return;
        }

        $active_level   = $this->session->userdata('active_level') ?? 400;
        $active_dept    = $this->session->userdata('active_dept') ?? 'Information Technology';
        $active_session = $this->session->userdata('active_session') ?? '2025/2026';

        // Find the active audit session ID for this department, level, and session
        $this->db->where('department', $active_dept);
        $this->db->where('level', $active_level);
        $this->db->where('academic_session', $active_session);
        $session_row = $this->db->get('faculty_audit_sessions')->row();

        if ($session_row) {
            // Deleting the session record automatically cascades and wipes out 
            // all student records and individual grades linked to it via foreign keys.
            $this->db->where('id', $session_row->id);
            $this->db->delete('faculty_audit_sessions');
        }

        // Redirect back to the dashboard with success feedback
        $this->session->set_flashdata('success', 'Level records have been cleared successfully.');
        redirect('result');
    }
}