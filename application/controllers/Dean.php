<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dean extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
        $this->load->library('session');
        $this->load->database();
        $this->load->model('Result_model');

        // Security Lock: Reject anyone who is not a Dean
        if (!$this->session->userdata('logged_in') || $this->session->userdata('role') !== 'dean') {
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
        $data['user_role']       = 'Dean';

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

    public function change_context() {
        if ($this->input->post('active_dept')) {
            $this->session->set_userdata('active_dept', $this->input->post('active_dept'));
        }
        if ($this->input->post('active_level')) {
            $this->session->set_userdata('active_level', $this->input->post('active_level'));
        }
        if ($this->input->post('active_semester')) {
            $this->session->set_userdata('active_semester', $this->input->post('active_semester'));
        }
        redirect($this->router->fetch_class());
    }

    public function approve_waiver() {
        // Read the matric number securely from a POST request to avoid slash errors
        $matric = $this->input->post('matric', TRUE);
        
        // Update the database to clear the student
        $this->db->where('matric', $matric);
        $updated = $this->db->update('faculty_audit_students', array(
            'grad_status_raw'   => 'cleared',
            'grad_status_label' => 'Waiver Approved'
        ));

        if ($updated) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update database.']);
        }
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

        $active_level   = $this->session->userdata('active_level') ?? 400;
        $active_dept    = $this->session->userdata('active_dept') ?? 'Information Technology';
        $active_session = $this->session->userdata('active_session') ?? '2025/2026';

        $candidates = $this->Result_model->get_audit_candidates($active_dept, $active_level, $active_session);

        if (empty($candidates)) {
            show_error('No active audit data found to export. Please ensure a result sheet has been uploaded for this department and level.', 404, 'Export Failed');
            return;
        }

        $filename = 'Dean_Senate_Broadsheet_' . str_replace(' ', '_', $active_dept) . '_' . date('Ymd_Hi') . '.xlsx';

        // Initialize PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dean Audit Broadsheet');

        $headers = ['S/N', 'Matric Number', 'Student Name', 'TUR', 'TUP', 'CGPA', 'Graduation Status', 'Waiver / Approval Status', 'Audit Flags'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $row_num = 2;
        $sn = 1;
        foreach ($candidates as $row) {
            $flag_labels = array();
            foreach ($row['flags'] as $flag) {
                $flag_labels[] = $flag['label'];
            }
            $flags_str = implode(' | ', $flag_labels);

            // Determine explicit approval state for the export
            $approval_status = 'N/A (Standard Pass)';
            if ($row['grad_status_raw'] === 'pending') {
                $approval_status = 'Pending Review (Awaiting Waiver)';
            } elseif ($row['grad_status_label'] === 'Waiver Approved') {
                $approval_status = 'Approved (Dean Waiver Granted)';
            } elseif ($row['grad_status_raw'] === 'uncleared') {
                $approval_status = 'Disapproved / Uncleared';
            }

            $sheet->setCellValue('A' . $row_num, $sn++);
            $sheet->setCellValue('B' . $row_num, $row['matric']);
            $sheet->setCellValue('C' . $row_num, $row['name']);
            $sheet->setCellValue('D' . $row_num, $row['tur']);
            $sheet->setCellValue('E' . $row_num, $row['tup']);
            $sheet->setCellValue('F' . $row_num, number_format($row['cgpa'], 2));
            $sheet->setCellValue('G' . $row_num, $row['grad_status_label']);
            $sheet->setCellValue('H' . $row_num, $approval_status);
            $sheet->setCellValue('I' . $row_num, $flags_str);
            
            $row_num++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}